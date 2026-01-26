<?php

namespace EscuelaIT\Test;

use EscuelaIT\Test\TestCase;
use EscuelaIT\APIKit\ListService;
use EscuelaIT\Test\Fixtures\Post;
use EscuelaIT\Test\Filters\TitleContainsFilter;
use PHPUnit\Framework\Attributes\Test;

class ListServiceTest extends TestCase
{

  #[Test]
  public function it_lists_no_posts_when_db_is_empty()
  {
    // Arrange: DB vacía
    $service = (new ListService())
      ->setListModel(Post::class)
      ->setSearchConfiguration([
        'perPage' => 10,
        'sortField' => 'id',
        'sortDirection' => 'asc',
      ]);

    // Act
    $results = $service->getResults();

    // Assert
    $this->assertEquals(0, $results['countItems']);
    $this->assertCount(0, $results['result']);
  }

  #[Test]
  public function it_throws_exception_when_list_model_not_defined()
  {
    $this->expectException(\EscuelaIT\APIKit\Exceptions\ListModelNotDefinedException::class);

    // Arrange: no se define el modelo
    $service = (new ListService())
      ->setSearchConfiguration([
        'perPage' => 10,
        'sortField' => 'id',
        'sortDirection' => 'asc',
      ]);

    // Act
    $service->getResults();
  }

  #[Test]
  public function it_lists_posts_with_pagination()
  {
    // Arrange: datos de prueba
    Post::factory()->count(15)->create();

    $service = (new ListService())
      ->setListModel(Post::class)
      ->setSearchConfiguration([
        'perPage' => 10,
        'sortField' => 'id',
        'sortDirection' => 'asc',
      ]);

    // Act
    $results = $service->getResults();

    // Assert
    $this->assertEquals(15, $results['countItems']);
    $this->assertCount(10, $results['result']); // 10 por página
  }

  #[Test]
  public function it_lists_posts_without_pagination()
  {
    // Arrange: datos de prueba
    Post::factory()->count(8)->create();

    $service = (new ListService())
      ->setListModel(Post::class)
      ->setSearchConfiguration([
        'perPage' => 10,
        'sortField' => 'id',
        'sortDirection' => 'asc',
      ])
      ->setPaginated(false);

      // Act
    $results = $service->getResults();

    // Assert
    $this->assertCount(8, $results);
  }

  #[Test]
  public function it_lists_posts_with_sorting()
  {
    // Arrange: datos de prueba
    Post::factory()->create(['title' => 'B Post']);
    Post::factory()->create(['title' => 'A Post']);
    Post::factory()->create(['title' => 'C Post']);

    $service = (new ListService())
      ->setListModel(Post::class)
      ->setSearchConfiguration([
        'sortField' => 'title',
        'sortDirection' => 'asc',
      ]);

    // Act
    $results = $service->getResults();

    // Assert
    $this->assertEquals('A Post', $results['result'][0]->title);
    $this->assertEquals('B Post', $results['result'][1]->title);
    $this->assertEquals('C Post', $results['result'][2]->title);
  }

  #[Test]
  public function it_lists_posts_without_filters()
  {
    // Arrange: datos de prueba
    Post::factory()->count(5)->create();

    $service = (new ListService())
      ->setListModel(Post::class)
      ->setSearchConfiguration([
        'perPage' => 10,
        'sortField' => 'id',
        'sortDirection' => 'asc',
      ]);

    // Act
    $results = $service->getResults();

    // Assert
    $this->assertEquals(5, $results['countItems']);
    $this->assertCount(5, $results['result']);
  }

  #[Test]
  public function it_filters_and_paginates_posts()
  {
    // Arrange: datos de prueba
    Post::factory()->count(3)->create(['status' => 'published']);
    Post::factory()->count(2)->create(['status' => 'draft']);

    $service = (new ListService())
      ->setListModel(Post::class)
      ->setSearchConfiguration([
        'filters' => [
          [
            'name' => 'status',
            'value' => 'published',
            'active' => true,
          ],
        ],
        'perPage' => 2,
        'sortField' => 'id',
        'sortDirection' => 'asc',
      ]);

    // Act
    $results = $service->getResults();

    // Assert
    $this->assertEquals(3, $results['countItems']);
    $this->assertCount(2, $results['result']);      // 2 por página
    $this->assertTrue(
      $results['result']->every(fn($post) => $post->status === 'published')
    );
  }

  #[Test]
  public function it_applies_custom_title_contains_filter()
  {
    Post::factory()->count(2)->create(['title' => 'Learn PHP', 'status' => 'published']);
    Post::factory()->count(1)->create(['title' => 'Laravel Tips', 'status' => 'published']);
    Post::factory()->count(2)->create(['title' => 'Advanced JavaScript', 'status' => 'draft']);

    $service = (new class extends ListService {
      protected function customFilters(): array
      {
        return [new TitleContainsFilter()];
      }
    })
      ->setListModel(Post::class)
      ->setSearchConfiguration([
        'filters' => [
          [
            'name' => 'title_contains',
            'value' => 'PHP',
            'active' => true,
          ],
        ],
        'perPage' => 10,
      ]);

    $results = $service->getResults();

    $this->assertEquals(2, $results['countItems']);
    $this->assertTrue(
      $results['result']->every(fn($post) => str_contains($post->title, 'PHP'))
    );
  }

  #[Test]
  public function it_applies_scope_via_belongsTo_and_relationId()
  {
    // Arrange: crear posts con IDs específicos
    Post::factory()->create(['id' => 1, 'title' => 'Post 1']);
    Post::factory()->create(['id' => 2, 'title' => 'Post 2']);
    Post::factory()->create(['id' => 3, 'title' => 'Post 3']);
    Post::factory()->create(['id' => 4, 'title' => 'Post 4']);
    Post::factory()->create(['id' => 5, 'title' => 'Post 5']);

    $service = (new ListService())
      ->setListModel(Post::class)
      ->setSearchConfiguration([
        'belongsTo' => 'greaterThanId',
        'relationId' => 2,
        'perPage' => 10,
        'sortField' => 'id',
        'sortDirection' => 'asc',
      ]);

    // Act
    $results = $service->getResults();

    // Assert
    $this->assertEquals(3, $results['countItems']); // Posts 3, 4, 5
    $this->assertCount(3, $results['result']);
    $this->assertTrue(
      $results['result']->every(fn($post) => $post->id > 2)
    );
  }
}
