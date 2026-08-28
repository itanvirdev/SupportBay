<?php

declare(strict_types=1);

namespace SupportBay\Dev;

use SupportBay\Core\Activator;
use SupportBay\Core\Testing\Assert;
use SupportBay\Core\Testing\FlowTest;
use SupportBay\Core\UpgradeManager;

final class InstallationLifecycleFlowTest extends FlowTest {
  protected static function title():string{return 'Installation Lifecycle Flow Test';}

  protected static function execute(...$services):void{
    $previous=get_option('sbay_settings',null);$previousVersion=get_option('sbay_version',null);
    try{
      update_option('sbay_settings',['footer_copyright_text'=>'Preserved custom value'],false);
      update_option('sbay_version',SBAY_VERSION,false);
      UpgradeManager::maybeUpgrade();
      $settings=get_option('sbay_settings',[]);
      Assert::true(is_array($settings)&&$settings['footer_copyright_text']==='Preserved custom value'&&array_key_exists('delete_data_on_uninstall',$settings),'Upgrade boot merges new defaults without overwriting saved values.');
      Assert::true(Activator::defaultSettings()['delete_data_on_uninstall']===false,'Destructive uninstall cleanup is explicitly opt-in.');
      Assert::true(class_exists(\SupportBay\Core\Uninstaller::class),'The guarded root uninstall entry resolves the lifecycle cleanup service.');
      $root=dirname(__DIR__,2);
      $distIgnore=(string)file_get_contents($root.'/.distignore');
      $releaseBuilder=(string)file_get_contents($root.'/tools/build-release.php');
      Assert::true(
        str_contains($distIgnore,'includes/Dev/')
        && str_contains($distIgnore,'assets/src/')
        && str_contains($distIgnore,'node_modules/')
        && str_contains($releaseBuilder,"'--no-dev'")
        && str_contains($releaseBuilder,"'--classmap-authoritative'")
        && str_contains($releaseBuilder,"'SupportBay/'"),
        'Production packaging excludes development sources and builds an optimized runtime autoloader.',
      );
    }finally{
      if($previous===null){delete_option('sbay_settings');}else{update_option('sbay_settings',$previous,false);}
      if($previousVersion===null){delete_option('sbay_version');}else{update_option('sbay_version',$previousVersion,false);}
    }
  }
}
