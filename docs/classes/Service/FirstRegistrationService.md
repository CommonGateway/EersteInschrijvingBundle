# CommonGateway\FirstRegistrationBundle\Service\FirstRegistrationService

## Methods

| Name | Description |
|------|-------------|
|[\_\_construct](#firstregistrationservice__construct)||
|[firstRegistrationHandler](#firstregistrationservicefirstregistrationhandler)|A first registration handler that is triggered by an action.|
|[getRolValues](#firstregistrationservicegetrolvalues)|Gets the values from the zaakEigenschappen of the zaak.|
|[getZaakEigenschappenValues](#firstregistrationservicegetzaakeigenschappenvalues)|Gets the values from the zaakEigenschappen of the zaak.|
|[getZaaktype](#firstregistrationservicegetzaaktype)|Gets the zaaktype object from the zaak.|
|[removeSelf](#firstregistrationserviceremoveself)|Recursively removes self parameters from object.|
|[sendFirstRegistration](#firstregistrationservicesendfirstregistration)|A first registration handler that is triggered by an action.|
|[zgwToFirstRegistrationHandler](#firstregistrationservicezgwtofirstregistrationhandler)|A first registration handler that is triggered by an action.|

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

* `(array) $data`
  : The data array
* `(array) $configuration`
  : The configuration array

**Return Values**

`array`

> A handler must ALWAYS return an array

<hr />

### FirstRegistrationService::getRolValues

**Description**

```php
public getRolValues (\ObjectEntity $zaaktypeObject, \ObjectEntity $zaakObject)
```

Gets the values from the zaakEigenschappen of the zaak.

**Parameters**

* `(\ObjectEntity) $zaaktypeObject`
  : The zaaktype object of the zaak.
* `(\ObjectEntity) $zaakObject`
  : The zaak object.

**Return Values**

`array`

> The values of the zaakEigenschappen.

<hr />

### FirstRegistrationService::getZaakEigenschappenValues

**Description**

```php
public getZaakEigenschappenValues (\ObjectEntity $zaaktypeObject, \ObjectEntity $zaakObject)
```

Gets the values from the zaakEigenschappen of the zaak.

**Parameters**

* `(\ObjectEntity) $zaaktypeObject`
  : The zaaktype object of the zaak.
* `(\ObjectEntity) $zaakObject`
  : The zaak object.

**Return Values**

`array`

> The values of the zaakEigenschappen.

<hr />

### FirstRegistrationService::getZaaktype

**Description**

```php
public getZaaktype (void)
```

Gets the zaaktype object from the zaak.

**Parameters**

`This function has no parameters.`

**Return Values**

`\ObjectEntity|null`

> The zaaktype from the zaak.

<hr />

### FirstRegistrationService::removeSelf

**Description**

```php
public removeSelf (array $object)
```

Recursively removes self parameters from object.

**Parameters**

* `(array) $object`
  : The object to remove self parameters from.

**Return Values**

`array`

> The cleaned object.

<hr />

### FirstRegistrationService::sendFirstRegistration

**Description**

```php
public sendFirstRegistration (array $data, array $configuration)
```

A first registration handler that is triggered by an action.

**Parameters**

* `(array) $data`
  : The data array
* `(array) $configuration`
  : The configuration array

**Return Values**

`array`

> A handler must ALWAYS return an array

<hr />

### FirstRegistrationService::zgwToFirstRegistrationHandler

**Description**

```php
public zgwToFirstRegistrationHandler (array $data, array $configuration)
```

A first registration handler that is triggered by an action.

**Parameters**

* `(array) $data`
  : The data array
* `(array) $configuration`
  : The configuration array

**Return Values**

`array`

> A handler must ALWAYS return an array

<hr />
