# project-rest-server
Project Rest Server is REST-api server for mysql  
Its based on Laravel 9+ framework

## Installation
#### # download from packagist
```
composer require kriit24/project-rest-server
```

#### # install package

```
php artisan project-rest-server:install
```

## Client for server  
#### react-native: [project-rest-client](https://www.npmjs.com/package/project-rest-client)


## NB!  
All the dynamic requests are POST methods because GET queries can distort data like umlauts and other special characters  
If u use json_encode to compile data then allways use it with option JSON_UNESCAPED_UNICODE



## DOCS


#### [config](https://github.com/kriit24/project-rest-server/tree/master/docs/config)
#### [relationship](https://github.com/kriit24/project-rest-server/tree/master/docs/relationship)
#### [usage](https://github.com/kriit24/project-rest-server/tree/master/docs/usage)
