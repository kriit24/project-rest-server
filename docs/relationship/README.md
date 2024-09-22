## [<<<](https://github.com/kriit24/project-rest-server/) RELATIONSHIP

#### # setup relational child table

App\Models\address.php - set event after inserted
```
protected $dispatchesEvents = [
    'inserted' => AddressAfterInsert::class,
];
```

App\Models\Events\AddressAfterInsert.php - set relation

```
declare(strict_types=1);

namespace App\Models\Events;

use App\Models\address;

class AddressAfterInsert extends address
{
    public function __construct($bindings, $tableData)
    {
        new \Project\RestServer\Models\Events\TableRelation($this->getTable(), $this->getKeyName(), $bindings, $tableData);
    }
}
```

#### # setup relational parent table

App\Models\objectT.php - set event before insert
```
 protected $dispatchesEvents = [
    'inserting' => ObjectBeforeInsert::class,
];
```

App\Models\Events\ObjectBeforeInsert.php - get relation by unique_id

```
declare(strict_types=1);

namespace App\Models\Events;

class ObjectBeforeInsert
{
    public function __construct(&$bindings)
    {
        if (isset($bindings['table_relation_unique_id'])) {

            $relation = \Project\RestServer\Models\Events\TableRelation::fetch($bindings['table_relation_unique_id']);
            if( !empty($relation) ) {
                
                //die(pre($relation));
                $bindings['object_address_id'] = $relation->table_relation_table_id;
            }
        }
    }
}
```

#### # request

```
$unique_id = unique_id();
```
Insert child row

```
curl -i -X POST \
   -H "uuid:KgfMRZG3GWG9hRP7tHQz5qukD9T4Yg" \
   -H "token:5751d40d2e9ab5a163d772fbc6d8f7027180ad65f1345cf60534b5d0d1f04facd35271987f05e0c8c9e8b5ba6a881bbe7bcce7521d5d995bdf08bc2ea00bc7dd" \
   -H "Content-Type:application/json" \
   -d \
'{"address_name":"test","data_unique_id":$unique_id}' \
 'https://localhost/post/localhost_1/address'
```

Insert parent row

```
curl -i -X POST \
   -H "uuid:KgfMRZG3GWG9hRP7tHQz5qukD9T4Yg" \
   -H "token:5751d40d2e9ab5a163d772fbc6d8f7027180ad65f1345cf60534b5d0d1f04facd35271987f05e0c8c9e8b5ba6a881bbe7bcce7521d5d995bdf08bc2ea00bc7dd" \
   -H "Content-Type:application/json" \
   -d \
'{"object_name":"test","table_relation_unique_id":$unique_id}' \
 'https://localhost/post/localhost_1/object'
```
