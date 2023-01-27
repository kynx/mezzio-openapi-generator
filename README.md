# kynx/mezzio-openapi-generator

Generate Mezzio REST APIs from OpenAPI specifications

**This is a Work in Progress**

## Goals

* [x] Generate route delegator config from spec (needs some refactoring)
* [x] Generate models from spec
* [ ] Generate handlers from spec
* [x] Generate model hydrators from spec (or just use constructor property promotion + JsonSerializable?)
* [ ] Handle exceptions in generate command
* [ ] Is schema `type` required? Are we validating the schema before processing?
* [ ] Bomb-proof `OpenApi` parsing: `OpenApi::validate()` doesn't catch lots of stuff

## Notes

### Performance:
https://github.com/cebe/php-openapi/issues/156

### OpenAPI 3.1:
