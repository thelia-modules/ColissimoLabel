# ColissimoLabel

Allows you to generate labels for orders passed through SoColissimo and ColissimoWs.

## Installation

### Manually

* Copy the module into ```<thelia_root>/local/modules/``` directory and be sure that the name of the module is ReadmeTest.
* Activate it in your thelia administration panel

### Composer

Add it in your main thelia composer.json file

```
composer require thelia/colissimo-label-module:~1.0.0
```

## Usage

Activating the module will add a button "ColissimoLabel" tor your left toolbar. Clicking it will
redirect you to the module page, which has 3 tabs.

- Bordereau tab : Allows you to generate a bordereau for every label generated since the last time you made a bordereau
- Label tab : Shows you the list of not sent orders, allowing you to generate labels for them, or to view ones that already exists
- Configuration tab : Lets you configure your module

The module also includes a new part to the delivery tab of the order edit page, allowing you to see every label
created for this order, as well as create new ones.

## Hook

 - order.edit-js : This hook is used to add a label list and label generation interface
 to the order edit page.
 
 - main.in-top-menu-items : Adds a button that redirects to the module page, in the left toolbar

## Loop

If your module declare one or more loop, describe them here like this :

[colissimolabel.label-info]

### Input arguments

|Argument |Description |
|---      |--- |
|**order_id** | An order ID |

### Output arguments

|Variable   |Description |
|---        |--- |
|$ORDER_ID    | The order ID |
|$HAS_ERROR    | (bool) Whether an error occured during the label generation or not |
|$ERROR_MESSAGE    | The error message |
|$WEIGHT    | The weight indicated on the label |
|$SIGNED    | (bool) Whether the label is a signed one or not |
|$TRACKING_NUMBER    | The order tracking number |
|$HAS_LABEL    | (bool) Whether the order has a label or not |
|$LABEL_TYPE    | The file extension of the label |
|$HAS_CUSTOMS_INVOICE    | (bool) Whether a customs invoice was created or not |
|$LABEL_URL    | The URL from which to download the URL |
|$CUSTOMS_INVOICE_URL    | The URL from which to download the customs invoice |
|$CLEAR_LABEL_URL    | The URL from which to delete the label |
|$CAN_BE_NOT_SIGNED    | (bool) Whether the order HAS to be signed or not |
|$ORDER_DATE    | The order date |

[colissimolabel.orders-not-sent]

### Input arguments

|Argument |Description |
|---      |--- |
|**with_prev_next_info** | See Thelia documentation |

### Output arguments

Same as an order loop, but only order that weren't sent or cancelled and that are paid for will be searched for.

## Other ?

If you have other think to put, feel free to complete your readme as you want.
