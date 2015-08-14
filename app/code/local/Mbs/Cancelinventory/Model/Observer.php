<?php

class Mbs_Cancelinventory_Model_Observer {

    public function salesOrderSaveAfter($observer) {

    	$order     = $observer->getEvent()->getOrder();
    	$oldStatus = $order->getOrigData('status');
    	$newStatus = $order->getData('status');

    	Mage::log("Cancelled Order Status Change Observer fired",null,"test.log");
    	Mage::log("Status before: ".$oldStatus."... Status after: ".$newStatus,null,"test.log");
    	if($oldStatus != 'canceled' && $newStatus == 'canceled') {
    		Mage::log("Status changed from ".$oldStatus." to canceled! Need to return items to inventory.",null,"test.log");
    		$add = true;
    	}
    	if($oldStatus == 'canceled' && $newStatus != 'canceled') {
    		Mage::log("Status changed from canceled to ".$newStatus."! Need to take items from inventory.",null,"test.log");	
    		$subtract = true;
    	}

    	foreach ($order->getAllItems() as $item) {
    		$qtyOrdered  = $item->getData('qty_ordered');
    		$id 		 = $item->getProductId();
    		$stockItem 	 = Mage::getModel('cataloginventory/stock_item')->loadByProduct($id);
    		$stockItemId = $stockItem->getId();
    		$newQty 	 = $oldQty = $stockItem->getData('qty');
    		
    		if($add) 	  {$newQty = $newQty + $qtyOrdered;}
    		if($subtract) {$newQty = $newQty - $qtyOrdered;}
    		$stockItem->setData('qty', $newQty)->save();
    		Mage::log("Item ID #".$id." quantity before: ".$oldQty.", after: ".$newQty,null,"test.log");
    		Mage::log("",null,"test.log");
    	}

    }

    public function salesOrderDeleteBefore($observer) {

        $order  = $observer->getEvent()->getOrder();
        $status = $order->getOrigData('status');

        // If the order was already cancelled, then we don't need to return items to inventory
        if($status != 'canceled') {
            foreach ($order->getAllItems() as $item) {
                $qtyOrdered  = $item->getData('qty_ordered');
                $id          = $item->getProductId();
                $stockItem   = Mage::getModel('cataloginventory/stock_item')->loadByProduct($id);
                $stockItemId = $stockItem->getId();
                $oldQty = $stockItem->getData('qty');
                $newQty = $oldQty + $qtyOrdered;
                
                $stockItem->setData('qty', $newQty)->save();
                Mage::log("Item ID #".$id." quantity before order deleted: ".$oldQty.", after: ".$newQty,null,"test.log");
                Mage::log("",null,"test.log");
            }
        }

    }
}

?>