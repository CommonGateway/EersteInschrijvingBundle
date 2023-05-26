# CommonGateway\FirstRegistrationBundle\Service\FirstRegistrationService

## Methods

| Name | Description |
|------|-------------|
|[\_\_construct](#firstregistrationservice__construct)||
|[firstRegistrationHandler](#firstregistrationservicefirstregistrationhandler)|A first registration handler that is triggered by an action.|
|[removeSelf](#firstregistrationserviceremoveself)|Recursively removes self parameters from object.|

### FirstRegistrationService::\_\_construct

**Description**

```php
 __construct (void)
```

**Parameters**

`This function has no parameters.`

**Return Values**

`void`

<hr />

### FirstRegistrationService::firstRegistrationHandler

**Description**

```php
public firstRegistrationHandler (array $data, array $configuration)
```

A first registration handler that is triggered by an action.

**Parameters**

*   `(array) $data`
    : The data array
*   `(array) $configuration`
    : The configuration array

**Return Values**

`array`

> A handler must ALWAYS return an array

<hr />

### FirstRegistrationService::removeSelf

**Description**

```php
public removeSelf (array $object)
```

Recursively removes self parameters from object.

**Parameters**

*   `(array) $object`
    : The object to remove self parameters from.

**Return Values**

`array`

> The cleaned object.

<hr />
