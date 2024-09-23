<?php

declare(strict_types=1);

namespace App\Tests\Api\Item;

use App\Entity\Datum;
use App\Entity\Item;
use App\Entity\Loan;
use App\Entity\Tag;
use App\Tests\ApiTestCase;
use App\Tests\Factory\CollectionFactory;
use App\Tests\Factory\DatumFactory;
use App\Tests\Factory\ItemFactory;
use App\Tests\Factory\LoanFactory;
use App\Tests\Factory\TagFactory;
use App\Tests\Factory\UserFactory;
use Symfony\Component\HttpFoundation\Response;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class ItemApiNotOwnerTest extends ApiTestCase
{
    use Factories;
    use ResetDatabase;

    public function test_cant_get_another_user_item(): void
    {
        // Arrange
        $user = UserFactory::createOne()->_real();
        $owner = UserFactory::createOne()->_real();
        $collection = CollectionFactory::createOne(['owner' => $owner]);
        $item = ItemFactory::createOne(['collection' => $collection, 'owner' => $owner]);

        // Act
        $this->createClientWithCredentials($user)->request('GET', '/api/items/' . $item->getId());

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function test_cant_get_another_user_item_collection(): void
    {
        // Arrange
        $user = UserFactory::createOne()->_real();
        $owner = UserFactory::createOne()->_real();
        $collection = CollectionFactory::createOne(['owner' => $owner]);
        $item = ItemFactory::createOne(['collection' => $collection, 'owner' => $owner]);

        // Act
        $this->createClientWithCredentials($user)->request('GET', '/api/items/' . $item->getId() . '/collection');

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function test_cant_get_another_user_item_data(): void
    {
        // Arrange
        $user = UserFactory::createOne()->_real();
        $owner = UserFactory::createOne()->_real();
        $collection = CollectionFactory::createOne(['owner' => $owner]);
        $item = ItemFactory::createOne(['collection' => $collection, 'owner' => $owner]);
        DatumFactory::createMany(3, ['item' => $item, 'owner' => $owner]);

        // Act
        $response = $this->createClientWithCredentials($user)->request('GET', '/api/items/' . $item->getId() . '/data');
        $data = $response->toArray();

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertSame(0, $data['totalItems']);
        $this->assertCount(0, $data['member']);
        $this->assertMatchesResourceCollectionJsonSchema(Datum::class);
    }

    public function test_cant_get_another_user_item_loans(): void
    {
        // Arrange
        $user = UserFactory::createOne()->_real();
        $owner = UserFactory::createOne()->_real();
        $collection = CollectionFactory::createOne(['owner' => $owner]);
        $item = ItemFactory::createOne(['collection' => $collection, 'owner' => $owner]);
        LoanFactory::createMany(3, ['item' => $item, 'owner' => $owner]);

        // Act
        $response = $this->createClientWithCredentials($user)->request('GET', '/api/items/' . $item->getId() . '/loans');
        $data = $response->toArray();

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertSame(0, $data['totalItems']);
        $this->assertCount(0, $data['member']);
        $this->assertMatchesResourceCollectionJsonSchema(Loan::class);
    }

    public function test_cant_get_another_user_item_related_items(): void
    {
        // Arrange
        $user = UserFactory::createOne()->_real();
        $owner = UserFactory::createOne()->_real();
        $collection = CollectionFactory::createOne(['owner' => $owner]);
        $relatedItems = ItemFactory::createMany(3, ['collection' => $collection, 'owner' => $owner]);
        $item = ItemFactory::createOne(['collection' => $collection, 'relatedItems' => $relatedItems, 'owner' => $owner]);

        // Act
        $response = $this->createClientWithCredentials($user)->request('GET', '/api/items/' . $item->getId() . '/related_items');
        $data = $response->toArray();

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertSame(0, $data['totalItems']);
        $this->assertCount(0, $data['member']);
        $this->assertMatchesResourceCollectionJsonSchema(Item::class);
    }

    public function test_cant_get_another_user_item_tags(): void
    {
        // Arrange
        $user = UserFactory::createOne()->_real();
        $owner = UserFactory::createOne()->_real();
        $collection = CollectionFactory::createOne(['owner' => $owner]);
        $tags = TagFactory::createMany(3, ['owner' => $owner]);
        $item = ItemFactory::createOne(['collection' => $collection, 'tags' => $tags, 'owner' => $owner]);

        // Act
        $response = $this->createClientWithCredentials($user)->request('GET', '/api/items/' . $item->getId() . '/tags');
        $data = $response->toArray();

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertSame(0, $data['totalItems']);
        $this->assertCount(0, $data['member']);
        $this->assertMatchesResourceCollectionJsonSchema(Tag::class);
    }

    public function test_cant_post_item_in_another_user_collection(): void
    {
        // Arrange
        $user = UserFactory::createOne()->_real();
        $owner = UserFactory::createOne()->_real();
        $collection = CollectionFactory::createOne(['owner' => $owner]);

        // Act
        $this->createClientWithCredentials($user)->request('POST', '/api/items/', ['json' => [
            'collection' => '/api/collections/' . $collection,
            'name' => 'Berserk',
        ]]);

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function test_cant_post_item_with_another_user_tag(): void
    {
        // Arrange
        $user = UserFactory::createOne()->_real();
        $collection = CollectionFactory::createOne(['owner' => $user]);
        $owner = UserFactory::createOne()->_real();
        $tag = TagFactory::createOne(['owner' => $owner]);

        // Act
        $this->createClientWithCredentials($user)->request('POST', '/api/items/', ['json' => [
            'collection' => '/api/collections/' . $collection,
            'name' => 'Berserk',
            'tags' => [$tag]
        ]]);

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function test_cant_post_item_with_another_user_datum(): void
    {
        // Arrange
        $user = UserFactory::createOne()->_real();
        $collection = CollectionFactory::createOne(['owner' => $user]);
        $owner = UserFactory::createOne()->_real();
        $datum = DatumFactory::createOne(['owner' => $owner]);

        // Act
        $this->createClientWithCredentials($user)->request('POST', '/api/items/', ['json' => [
            'collection' => '/api/collections/' . $collection,
            'name' => 'Berserk',
            'data' => [$datum]
        ]]);

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function test_cant_put_another_user_item(): void
    {
        // Arrange
        $user = UserFactory::createOne()->_real();
        $owner = UserFactory::createOne()->_real();
        $collection = CollectionFactory::createOne(['owner' => $owner]);
        $item = ItemFactory::createOne(['name' => 'Frieren #1', 'collection' => $collection, 'owner' => $owner]);

        // Act
        $this->createClientWithCredentials($user)->request('PUT', '/api/items/' . $item->getId(), ['json' => [
            'name' => 'Berserk',
        ]]);

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function test_cant_patch_another_user_item(): void
    {
        // Arrange
        $user = UserFactory::createOne()->_real();
        $owner = UserFactory::createOne()->_real();
        $collection = CollectionFactory::createOne(['owner' => $owner]);
        $item = ItemFactory::createOne(['name' => 'Frieren #1', 'collection' => $collection, 'owner' => $owner]);

        // Act
        $this->createClientWithCredentials($user)->request('PATCH', '/api/items/' . $item->getId(), [
            'headers' => ['Content-Type: application/merge-patch+json'],
            'json' => [
                'name' => 'Berserk',
            ],
        ]);

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function test_cant_delete_another_user_item(): void
    {
        // Arrange
        $user = UserFactory::createOne()->_real();
        $owner = UserFactory::createOne()->_real();
        $collection = CollectionFactory::createOne(['owner' => $owner]);
        $item = ItemFactory::createOne(['collection' => $collection, 'owner' => $owner]);

        // Act
        $this->createClientWithCredentials($user)->request('DELETE', '/api/items/' . $item->getId());

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }
}
