# Rumblebros Bespoke Shipping Setup
This is our setup for the Shopify Plugin [Bespoke Shipping](https://apps.shopify.com/custom-shipping-rates)  

This plugin utilizes PHP to create functions to check for various things at checkout. These range from ship from location, ship to location, item quantity, item price, what collection an item belongs to, etc etc... There's a lot of customization to be utilized with this plugin.  

We currently use it to show custom shipping rates for UPS and USPS depending on whether the customer is closer to Pennsylvania or Nevada. Those are our two warehouse locations for automotive parts.
- If an order is made up of only small stickers, it will also show a flat rate option for stickers that does not include tracking (first class mail).
- If an order contains Bavar Wheels, Rota Wheels, or Seibon Carbon, it shows their flat rate shipping for US states.
- If an order contains any aftermarket part, it will only show UPS shipping rates at checkout.