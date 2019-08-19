INTRODUCTION
----------

This module implement Return order functionality to Drupal Commerce.

REQUIREMENTS
------------

This module requires the following modules:

 * Commerce (https://www.drupal.org/project/commerce)

INSTALLATION
------------

There is no special requirements for install process.

CONFIGURATION
-------------

* Return order list /admin/commerce/commerce_return
* Return reasons list admin/commerce/rma_reason
* Return types list admin/commerce/rma_type

DESCRIPTION
-----------

RMA functional is availible only for completed orders as operation on
standard order list admin/commerce/orders

We don't use special bundle of commerce_orders beacuase we have very special
flow and checkout process is completely not needed for return order.

User can create return from his standard orders list on creating process.
User can change amount of returned items from each order (partial return).
Manager can initiate return for any completed order creation process is the
same as for regular user.
When user submit the return manager can confirm or decline it from the
list of returns /admin/commerce/commerce_return. When the client recive the
return manager should set return order as completed manually
