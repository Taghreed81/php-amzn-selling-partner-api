<?php

namespace Jasara\AmznSPA\DataTransferObjects\Schemas\FulfillmentInbound;

use Jasara\AmznSPA\DataTransferObjects\Validators\StringEnumValidator;
use Spatie\DataTransferObject\DataTransferObject;

class InvalidASINSchema extends DataTransferObject
{
    public ?string $asin;

    #[StringEnumValidator(['DoesNotExist', 'InvalidASIN'])]
    public string $error_reason;
}
