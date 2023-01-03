# kynx/mezzio-openapi-generator

Generate Mezzio REST APIs from OpenAPI specifications

**This is a Work in Progress**

## Goals

* [ ] Generate route delegator config from spec (needs some refactoring)
* [x] Generate models from spec
* [ ] Generate handlers from spec
* [ ] Generate model hydrators from spec (or just use constructor property promotion + JsonSerializable?)

## Notes

### Autowiring:
https://docs.mezzio.dev/mezzio/v3/cookbook/autowiring-routes-and-pipelines/

### Performance:
https://github.com/cebe/php-openapi/issues/156

### OpenAPI 3.1:
