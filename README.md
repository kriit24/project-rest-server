# project-rest-server
Project Rest Server is REST-api server for mysql  
Its based on Laravel 9+ framework

## Installation
#### # install from composer
```
composer require kriit24/project-rest-server
```

#### # install package migrations

```
php artisan project-rest-server:install
```

## Client for server  
#### react-native: [project-rest-client](https://www.npmjs.com/package/project-rest-client)


## NB!  
All the dynamic requests are POST methods because GET queries can distort data like umlauts and other special characters  
If u use json_encode to compile data then allways use it with option JSON_UNESCAPED_UNICODE



## DOCS


#### [config](https://github.com/kriit24/project-rest-server/docs/config)
#### [relationship](https://github.com/kriit24/project-rest-server/docs/relationship)
#### [usage](https://github.com/kriit24/project-rest-server/usage)
