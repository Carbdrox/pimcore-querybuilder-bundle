# Pimcore Querybuilder Bundle
[![Total Downloads](https://img.shields.io/packagist/dt/Carbdrox/pimcore-querybuilder-bundle.svg?style=flat)](https://packagist.org/packages/carbdrox/pimcore-querybuilder-bundle)
[![Latest Version](https://img.shields.io/github/tag/Carbdrox/pimcore-querybuilder-bundle.svg?style=flat&label=release)](https://github.com/Carbdrox/pimcore-querybuilder-bundle/tags)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat)](LICENSE)

This bundle adds a Service to your [Pimcore](https://github.com/pimcore/pimcore) project, which can resolve vite assets.

## Installation

### Using composer

```
composer require carbdrox/pimcore-querybuilder-bundle
```

## Usage
You can either use the service directly or use the provided trait.

### Using the service directly

```php
use QueryBuilderBundle\Service\QueryService;
use Pimcore\Model\DataObject\DummyData;  
$queryService = new QueryService(DummyData::class);
```

### Using the provided trait

if you want to use the provided trait, you must either add `\QueryBuilderBundle\Traits\HasQueryBuilder` in your 
DataObject definition in the Use(traits) field, or extend your DataObject class and insert 
`use \QueryBuilderBundle\Traits\HasQueryBuilder;` there.

### Interface

#### Trait
The trait adds the following static methods to your DataObject class:

| Method                                                                                	| Returns        	|
|---------------------------------------------------------------------------------------	|----------------	|
| query()                                                                               	| QueryBuilder   	|
| orderBy(string $field, string $order = 'asc')                                         	| QueryBuilder   	|
| limit(int $limit)                                                                     	| QueryBuilder   	|
| offset(int $offset)                                                                   	| QueryBuilder   	|
| count()                                                                               	| int            	|
| first()                                                                               	| Concrete\|null 	|
| all()                                                                                 	| array          	|
| where(string\|\Closure $field, string $operation, mixed $value, bool $escaped = true) 	| QueryBuilder   	|

#### Service
The service provides the following methods:

| Method                                                                                             	| Returns             	|
|----------------------------------------------------------------------------------------------------	|---------------------	|
| where(string\|\Closure $field, string $operation = '=', mixed $value = '', bool $escaped = true)   	| QueryBuilder        	|
| whereNested(\Closure $callback)                                                                    	| QueryBuilder        	|
| orWhere(string\|\Closure $field, string $operation = '=', mixed $value = '', bool $escaped = true) 	| QueryBuilder        	|
| orWhereNested(\Closure $callback)                                                                  	| QueryBuilder        	|
| join(string $tableName, string $name)                                                              	| QueryBuilder        	|
| limit(int $limit)                                                                                  	| QueryBuilder        	|
| offset(int $offset)                                                                                	| QueryBuilder        	|
| orderBy(string $field, string $order = 'asc')                                                      	| QueryBuilder        	|
| groupBy(string $column)                                                                            	| QueryBuilder        	|
| count()                                                                                            	| int                 	|
| get()                                                                                              	| array               	|
| first()                                                                                            	| Concrete\|null      	|
| all()                                                                                              	| array               	|
| toSql()                                                                                            	| string              	|
| paginate(int $page = 1, int $perPage = 10)                                                         	| PaginationInterface 	|


## Contributing

Thank you for considering contributing! The contribution guide can be found in the [CONTRIBUTING.md](CONTRIBUTING.md).

## Code of Conduct

Please review and abide by the [Code of Conduct](CODE_OF_CONDUCT.md).

## License

The Pimcore Vite bundle is licensed under the [MIT license](LICENSE.md).

## TODOs
- [ ] Add tests
- [ ] The join method needs to be revised so that you don't have to manually join from the alphabetically smaller class to the larger one.