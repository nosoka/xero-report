## xero reports
A laravel package to generate xero reports and send as slack notifications. This is compatible with laravel version 5.5/5.6/5.7

## Installation
- Since the package is not published to packagist.org, yet. you can install it from github by adding following to composer.json in your existing laravel project
    ```
        "repositories": [
            {
                "type": "vcs",
                "url": "git@github.com:pasok/xero-report.git"
            }
        ],
    ```
- Install the package
    ```
    $ composer require nosok/xero-report
    ```

- Publish config file.
    ```
    $ php artisan vendor:publish --provider="Nosok\XeroReport\XeroReportServiceProvider"
    ```

## Configuration
- Please update xero oauth config in config/xeroreport.php
    - ref: [developer.xero.com/documentation/api-guides/create-publicprivate-key](https://developer.xero.com/documentation/api-guides/create-publicprivate-key)

## Create report
- Run following to create/send report
    ```
    php artisan xero:report
    ```
