<?php
declare(strict_types=1);
namespace SupportBay\Modules\Categories\Entities;
use SupportBay\Core\Entities\Entity;
use SupportBay\Modules\Categories\Enums\CategoryStatus;
final class Category extends Entity {
  public function __construct(private readonly int $id,private readonly string $name,private readonly string $slug,private readonly ?string $description,private readonly ?int $departmentId,private readonly CategoryStatus $status,private readonly ?string $color,private readonly int $sortOrder,private readonly string $createdAt,private readonly string $updatedAt){}
  public function toArray():array{return ['id'=>$this->id,'name'=>$this->name,'slug'=>$this->slug,'description'=>$this->description,'department_id'=>$this->departmentId,'status'=>$this->status->value,'color'=>$this->color,'sort_order'=>$this->sortOrder,'created_at'=>$this->createdAt,'updated_at'=>$this->updatedAt];}
  public function id():int{return $this->id;} public function name():string{return $this->name;} public function slug():string{return $this->slug;} public function description():?string{return $this->description;} public function departmentId():?int{return $this->departmentId;} public function status():CategoryStatus{return $this->status;} public function color():?string{return $this->color;} public function sortOrder():int{return $this->sortOrder;} public function createdAt():string{return $this->createdAt;} public function updatedAt():string{return $this->updatedAt;} public function isActive():bool{return $this->status===CategoryStatus::ACTIVE;}
}
