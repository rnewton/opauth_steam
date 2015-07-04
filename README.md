Opauth-Steam
=============
[Opauth][1] strategy for Steam authentication.

Opauth is a multi-provider authentication framework for PHP.

Getting started
----------------
   Install Opauth-Steam:
   ```bash
   composer require rnewton/opauth_steam
   ```


Strategy configuration
----------------------

Required parameters:

```php
<?php
'Steam' => array(
    'key' => 'YOUR API KEY',
    'domain' => 'YOUR DOMAIN'
)
```

License
---------
Opauth-Steam is MIT Licensed  
Copyright © 2015 Robert Newton

[1]: https://github.com/uzyn/opauth