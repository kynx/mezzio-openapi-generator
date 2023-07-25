# TODO

* [x] Routing: allow specification extension to add middleware to operation
* [ ] Test generation. See https://packagist.org/packages/canvural/php-openapi-faker
* [ ] Models: switch to public properties and no getters? If we do this we will have to loose the interfaces for `allOf` constructs.
* [ ] `Model\Operation\User\CreateWithArray\Post\Operation::getRequestBody()` - should be `list<User>`
* [ ] File uploads
* [ ] Operations: add metadata and docblocks
* [ ] Add array shape annotations to hydrators
* [ ] Add assertions for `$body` type to operation factory `getRequestBody()` based on expectations of hydrator
* [ ] Add `ArrayHydratorInterface` and move array hydration logic to that?
* [x] Defaults not applied to query parameters
* [x] Create factories for all handlers, not just those with serializer dependencies
* [ ] Array responses should accept iterables (change all `array` types to `iterable`?)
* [ ] Handle exceptions in generate command
* [x] Is schema `type` required? Are we validating the schema before processing?
* [ ] Bomb-proof `OpenApi` parsing: `OpenApi::validate()` doesn't catch lots of stuff
* [ ] `Request::getResponseBody()` for array types needs docblock type
* [ ] Ran some benchmarks. Static class properties and constants have equal performance, readonly properties defined
      in constructor are ~10% slower, properties with default values are around 10% faster!!
* [ ] Switch to `iterator` for array types on responses
* [ ] Response headers - generate header model and populate from template on output
* [ ] Revisit response serialization. It gets really messy working out what to hydrate when
      there are multiple mime types / arrays / etc
* [ ] Inject `Dumper` and see if we can get it respecting line-lengths properly
* [ ] UnionProperty needs refactor to handle members that are arrays, enums etc
