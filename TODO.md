# TODO

* [ ] Routing: allow specification extension to add middleware to operation
* [ ] Test generation. See https://packagist.org/packages/canvural/php-openapi-faker
* [ ] Models: switch to public properties and no getters? If we do this we will have to loose the interfaces for `allOf` constructs.
* [ ] `Model\Operation\User\CreateWithArray\Post\Operation::getRequestBody()` - should be `list<User>`
* [ ] File uploads
* [ ] Operations: add metadata and docblocks
* [ ] Add array shape annotations to hydrators
* [ ] Add assertions for `$body` type to operation factory `getRequestBody()` based on expectations of hydrator
* [ ] Add `ArrayHydratorInterface` and move array hydration logic to that
* [ ] Defaults not applied to query parameters
* [ ]