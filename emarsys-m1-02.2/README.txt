1. Do not restrict setEmail/setCustomerId to just logged customer
2. Order Confirmation: Use order email address [setEmail] from the order confirmation success page
3. Subscription Confirmation: Use subscriber email address [setEmail] on the subscription confirmation page
4. Registration Success: Use customer email address [setEmail] on the registration confirmation page
5. Cart - Price issue - it should be Subtotal - Ref: http://documentation.emarsys.com/resource/b2c-cloud/web-extend/javascript-api/#cart
6. Bug Fixes
    a. Installer Script: MySql 5.7 Compatibility
    b. Smart Insight: Headers should always be there in Sales Export CSV file even the queue is empty to avoid the error on Emarsys Side
    c. Transactional Email: Fix the issue with Multi Store with different email template per store
    d. Flat Catalog SI Export Issue
    e. Subscriber export on initial load missing customers
7. Multi Shipping Order Confirmation page - purchase command missing
8. Make sure the implementation works even the Magento full page cache & block cache enabled
9. Multi Store Support
10. Magento Community & Enterprise Edition Support
11. Base Currency Support
12. Include Tax In purchase Feature
13. Ajax feature implemented to avoid the caching issues
14. Purchase - Price Issue - it should be a subtotal excluding the discount amount
 
