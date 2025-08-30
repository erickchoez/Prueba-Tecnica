<?php
use PHPUnit\Framework\TestCase;
use App\Models\ProductModel;
use App\Core\Database;

final class ProductTest extends TestCase {
  public static function setUpBeforeClass(): void {
   
  }

  public function testCreateAndList(): void {
    $m = new ProductModel();
    $id = $m->create('TEST-SKU','Producto Test', 10.50, 5);
    $this->assertGreaterThan(0, $id);
    $rows = $m->list('Producto Test', 'created_at','desc',1,10);
    $this->assertNotEmpty($rows);
  }

  public function testUpdateAndDelete(): void {
    $m = new ProductModel();
    $id = $m->create('UPD-SKU','X', 1.00, 1);
    $aff = $m->update($id,'UPD-SKU','Y', 2.00, 2);
    $this->assertGreaterThan(0, $aff);
    $del = $m->delete($id);
    $this->assertGreaterThan(0, $del);
  }
}
