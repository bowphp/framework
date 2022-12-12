<?php

namespace Bow\Tests\Support\CQRS;

use FetchPetQueryHandler;
use PHPUnit\Framework\TestCase;
use Bow\Support\CQRS\Registration as CQRSRegistration;
use Bow\Support\CQRS\CQRSException;
use Bow\Tests\Database\Stubs\PetModelStub;
use Bow\Tests\Support\CQRS\Queries\FetchPetQuery;
use Bow\Tests\Support\CQRS\Queries\FetchAllPetQuery;
use Bow\Tests\Support\CQRS\Commands\CreatePetCommand;
use Bow\Tests\Support\CQRS\Commands\CreatePetCommandHandler;

class CQRSTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        CQRSRegistration::commands([
            CreatePetCommand::class => CreatePetCommandHandler::class
        ]);

        CQRSRegistration::queries([
            FetchPetQuery::class => FetchPetQueryHandler::class
        ]);
    }

    public function test_get_handler_should_return_the_right_handler()
    {
        $query_handler = CQRSRegistration::getHandler(new FetchPetQuery(1));

        $this->assertInstanceOf(FetchPetQueryHandler::class, $query_handler);
    }

    public function test_get_handler_should_throw_error()
    {
        $this->expectException(CQRSException::class);
        $query_handler = CQRSRegistration::getHandler(new FetchAllPetQuery());
    }

    public function test_query_bus()
    {
        $query_bus = new QueryBus();
        $pet = $query_bus->execute(new FetchPetQuery(1));

        $this->assertInstanceOf(PetModelStub::class, $pet);
        $this->assertEquals($pet->name, 'Milou');
    }
}
