<?php
use PHPUnit\Framework\TestCase;
use App\Models\OrderModel;
use App\Models\ProductModel;

final class OrderTest extends TestCase {
  public function testCreateOrderInsufficientStock(): void {
    $pm = new ProductModel();
    $pid = $pm->create('LOW-STK','Low', 5.00, 0);
    $om = new OrderModel();
    $this->expectException(Throwable::class);
    $om->create([['producto_id'=>$pid,'cantidad'=>1,'precio_unitario'=>5.00]]);
  }

  public function testOrderTotalsRule(): void {
    $pm = new ProductModel();
    $pid = $pm->create('ORD-100','AA', 60.00, 10); 
    $om = new OrderModel();
    $order = $om->create([['producto_id'=>$pid,'cantidad'=>2,'precio_unitario'=>60.00]]);
    $this->assertEquals(120.00, (float)$order['subtotal']);
    $this->assertEquals(12.00, (float)$order['descuento']); 
    $this->assertEquals(12.96, (float)$order['iva']); 
    $this->assertEquals(120.96, (float)$order['total']);
  }
}
