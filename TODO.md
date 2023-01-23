# TODO

* [x] User-supplied type -> class mapping that returns a `ClassString`. Also simplifies our logic mapping these - no more `PropertyType::isClass()`
* [ ] Routing: allow specification extension to add middleware to operation
* [ ] Routing: middleware to inject `Operation` into route. Allows guard middleware added above to consume operation
* [ ] Test generation. See https://packagist.org/packages/canvural/php-openapi-faker
* [x] Models: namespace to follow schema structure (`Model\Paths` `Model\Schema`, `Model\Response` etc namespaces)
* [ ] Models: switch to public properties and no getters? If we do this we will have to loose the interfaces for `allOf` constructs.
* [x] Models: add docblocks from metadata
* [ ] `Model\Operation\User\CreateWithArray\Post\Operation::getRequestBody()` - should be `list<User>`
* [x] --`Model\Path\Pet\PetId\UploadImage\Post\RequestBody::$additionalMetadata` - what's `$additionalMetadata` doing there?-- 'sOK, not `additionalProperties` 
* [x] `Operation\Pet\Post\Operation` - why `Pet|Pet`?
* [ ] File uploads
* [ ] Operations: add metadata and docblocks
* [ ] Add array shape annotations to hydrators
* [ ] Add assertions for `$body` type to operation factory `getRequestBody()` based on expectations of hydrator
* [ ] Add `ArrayHydratorInterface` and move array hydration logic to that