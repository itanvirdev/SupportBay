<?php
declare(strict_types=1);
namespace SupportBay\Modules\Categories\Repositories;
use SupportBay\Core\Database\Repository;use SupportBay\Modules\Categories\Database\CategorySchema;use SupportBay\Modules\Categories\Entities\Category;use SupportBay\Modules\Categories\Enums\CategoryStatus;
final class CategoryRepository extends Repository {
  protected function table():string{return CategorySchema::tableName();}
  public function create(array $data):int{return $this->insert(['name'=>$data['name'],'slug'=>$data['slug'],'description'=>$data['description'],'status'=>$data['status'],'color'=>$data['color'],'sort_order'=>$data['sort_order'],'created_at'=>$this->now(),'updated_at'=>$this->now()],['%s','%s','%s','%s','%s','%d','%s','%s']);}
  public function find(int $id):?Category{return $this->findById($id);} public function findBySlug(string $slug):?Category{return $this->first(['slug'=>$slug]);}
  public function all():array{return $this->findAll('sort_order');} public function active():array{return $this->findWhere(['status'=>CategoryStatus::ACTIVE->value],'sort_order');}
  public function nextSortOrder():int{global $wpdb;return ((int)$wpdb->get_var("SELECT MAX(sort_order) FROM {$this->table()}"))+1;}
  public function update(int $id,array $data):bool{$data['updated_at']=$this->now();return $this->updateById($id,$data);} public function delete(int $id):bool{return $this->deleteById($id);}
  protected function hydrate(array $row):Category{return new Category(id:(int)$row['id'],name:(string)$row['name'],slug:(string)$row['slug'],description:$row['description']!==null?(string)$row['description']:null,status:CategoryStatus::from($row['status']),color:$row['color']!==null?(string)$row['color']:null,sortOrder:(int)$row['sort_order'],createdAt:(string)$row['created_at'],updatedAt:(string)$row['updated_at']);}
}
