<?php

namespace Tests\Feature\Api\Administration;

use Faker\Factory as Faker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use ProcessMaker\Model\Process;
use ProcessMaker\Model\ProcessCategory;
use ProcessMaker\Model\Role;
use ProcessMaker\Model\User;
use Tests\Feature\Api\ApiTestCase;

class ProcessCategoryManagerTest extends ApiTestCase
{
    use DatabaseTransactions;

    const API_TEST_CATEGORY = '/api/1.0/category/';
    const API_TEST_CATEGORIES = '/api/1.0/categories';
    
    private $testUserRole = Role::PROCESSMAKER_ADMIN;

    /**
     * Log in a user before each test run 
     */
    private function login()
    {
        $user = factory(User::class)->create([
            'password' => Hash::make('password'),
            'role_id' => Role::where('code', $this->testUserRole)->first()->id,
        ]);
        $this->auth($user->username, 'password');
    }

    /**
     * Test access control for the process category endpoints.
     */
    public function testAccessControl()
    {
        $this->testUserRole = Role::PROCESSMAKER_OPERATOR;
        $this->login();

        $catUid = factory(ProcessCategory::class)->create()->uid;

        $response = $this->api('GET', self::API_TEST_CATEGORIES);

        $response->assertStatus(403);

        $response = $this->api('POST', self::API_TEST_CATEGORY, []);
        $response->assertStatus(403);

        $response = $this->api('GET', self::API_TEST_CATEGORY . $catUid);
        $response->assertStatus(403);

        $response = $this->api('PUT', self::API_TEST_CATEGORY . $catUid, []);
        $response->assertStatus(403);

        $response = $this->api('DELETE', self::API_TEST_CATEGORY . $catUid);
        $response->assertStatus(403);
    }

    /**
     * Test get the list of categories.
     */
    public function testGetListOfCategories()
    {
        $this->login();

        //Create test categories
        $process = factory(Process::class)->create();
        $processCategory = $process->category;

        $response = $this->api('GET', self::API_TEST_CATEGORIES);
        $response->assertStatus(200);
        $response->assertJsonStructure();
        $response->assertJsonFragment(
            [
                "cat_uid"             => $processCategory->uid,
                "cat_name"            => $processCategory->name,
                "cat_status"          => $processCategory->status,
                "cat_total_processes" => 1,
            ]
        );
    }

    /**
     * Test get the list of categories filter
     */
    public function testGetListOfCategoriesFilter()
    {
        $this->login();
        $process = factory(Process::class)->create();
        
        //Create test categories
        $processCategory = $process->category;
        $otherCategory = factory(ProcessCategory::class)->create();

        //Test filter
        $response = $this->api('GET', self::API_TEST_CATEGORIES . '?filter=' . urlencode($processCategory->name));
        $response->assertStatus(200);
        $response->assertJsonStructure();

        $response->assertJsonFragment(
            [
                "cat_uid"             => $processCategory->uid,
                "cat_name"            => $processCategory->name,
                "cat_status"          => $processCategory->status,
                "cat_total_processes" => 0,
            ]
        );

    }
    /**
     * Test sorting by category name
     */
    public function testGetListOfCategoriesSorted()
    {
        $this->login();
        factory(ProcessCategory::class)->create([
            'name' => 'first test category'
        ]);
        
        factory(ProcessCategory::class)->create([
            'name' => 'second test category'
        ]);
        
        // defaults sort to name ASC
        $response = $this->api('GET', self::API_TEST_CATEGORIES . '?filter=test');
        $json = $response->json();
        $this->assertEquals($json[0]['cat_name'], 'first test category');
        $this->assertEquals($json[1]['cat_name'], 'second test category');
        
        // sort by name ASC
        $response = $this->api('GET', self::API_TEST_CATEGORIES . '?filter=test&sort_by=name&sort_order=ASC');
        $json = $response->json();
        $this->assertEquals($json[0]['cat_name'], 'first test category');
        $this->assertEquals($json[1]['cat_name'], 'second test category');
        
        // sort by name DESC
        $response = $this->api('GET', self::API_TEST_CATEGORIES . '?filter=test&sort_by=name&sort_order=DESC');
        $json = $response->json();
        $this->assertEquals($json[0]['cat_name'], 'second test category');
        $this->assertEquals($json[1]['cat_name'], 'first test category');
    }

    /**
     * Test get the list of categories without results
     */
    public function testGetFilterWithoutResult()
    {
        $this->login();
        //Create test categories
        $processCategory = factory(ProcessCategory::class)->create();
        factory(ProcessCategory::class)->create();
        factory(Process::class)->create([
            'process_category_id' => $processCategory->id
        ]);
        //Test filter not found
        $response = $this->api('GET', self::API_TEST_CATEGORIES . '?filter=NOT_FOUND_TEXT');
        $response->assertStatus(200);
        $response->assertJsonStructure();
        $this->assertCount(0, $response->json());
    }

    /**
     * Test get the list of categories invalid parameter start
     */
    public function testGetListOfCategoriesInvalidStart()
    {
        $this->login();
        //Create test categories
        $processCategory = factory(ProcessCategory::class)->create();
        factory(ProcessCategory::class)->create();
        factory(Process::class)->create([
            'process_category_id' => $processCategory->id
        ]);
        $response = $this->api('GET', self::API_TEST_CATEGORIES . '?start=INVALID');
        $response->assertStatus(422);
        $this->assertEquals(
            __('validation.numeric', ['attribute' => 'start']), $response->json()['error']['message']
        );
    }

    /**
     * Test get the list of categories invalid parameter limit
     */
    public function testGetListOfCategoriesInvalidLimit()
    {
        $this->login();
        //Create test categories
        $processCategory = factory(ProcessCategory::class)->create();
        factory(ProcessCategory::class)->create();
        factory(Process::class)->create([
            'process_category_id' => $processCategory->id
        ]);
        $response = $this->api('GET', self::API_TEST_CATEGORIES . '?limit=INVALID');
        $response->assertStatus(422);
        $this->assertEquals(
            __('validation.numeric', ['attribute' => 'limit']), $response->json()['error']['message']
        );
    }

    /**
     * Test get the list of categories with start and limit
     */
    public function testGetListOfCategoriesStartLimit()
    {
        $this->login();
        //Create test categories
        $processCategory = factory(ProcessCategory::class)->create();
        factory(ProcessCategory::class)->create();
        factory(Process::class)->create([
            'process_category_id' => $processCategory->id
        ]);

        //Test start and limit
        $response = $this->api('GET', self::API_TEST_CATEGORIES . '?filter=' . urlencode($processCategory->name) . '&start=0&limit=1');
        $response->assertStatus(200);
        $response->assertJsonStructure();
        $response->assertJsonFragment(
            [
                "cat_uid"             => $processCategory->uid,
                "cat_name"            => $processCategory->name,
                "cat_total_processes" => 0,
            ]
        );
        $this->assertCount(1, $response->json());
    }

    /**
     * Test the creation of process categories.
     */
    public function testCreateProcessCategory()
    {
        $this->login();
        $faker = Faker::create();

        $processCategory = factory(ProcessCategory::class)->make();
        $data = [
            "name" => $processCategory->name,
            "status" => $processCategory->status,
        ];
        $response = $this->api('POST', self::API_TEST_CATEGORY, $data);
        $response->assertStatus(201);
        $response->assertJsonStructure();
        $processCategoryJson = $response->json();
        $processCategory = ProcessCategory::where('uid', $processCategoryJson['cat_uid'])
            ->first();
        $this->assertNotNull($processCategory);
        $response->assertJsonFragment(
            [
                "cat_uid" => $processCategory->uid,
                "cat_name" => $processCategory->name,
                "cat_status" => $processCategory->status,
                "cat_total_processes" => 0,
            ]
        );

        //Validate required cat_name
        $response = $this->api('POST', self::API_TEST_CATEGORY, []);
        $response->assertStatus(422);
        $this->assertEquals(
            __('validation.required', ['attribute' => 'name']), $response->json()['error']['message']
        );

        //Validate creation of duplicated category
        $response = $this->api('POST', self::API_TEST_CATEGORY, $data);
        $response->assertStatus(422);
        $this->assertEquals(
            __('validation.unique', ['attribute' => 'name']), $response->json()['error']['message']
        );

        //Validate invalid large name
        $data = [
            "name" => $faker->sentence(100),
            "status" => $processCategory->status,
        ];
        $response = $this->api('POST', self::API_TEST_CATEGORY, $data);
        $response->assertStatus(422);
        $this->assertEquals(
            __('validation.max.string', ['attribute' => 'name', 'max' => 100]), $response->json()['error']['message']
        );
    }

    /**
     * Test the update of process categories.
     */
    public function testUpdateProcessCategory()
    {
        $this->login();
        $faker = Faker::create();

        $processCategoryExisting = factory(ProcessCategory::class)->create();
        $processCategory = factory(ProcessCategory::class)->create();
        $catUid = $processCategory->uid;
        $data = [
            "name" => $faker->name(),
            "status" => ProcessCategory::STATUS_ACTIVE,
        ];
        $response = $this->api('PUT', self::API_TEST_CATEGORY . $catUid, $data);
        $response->assertStatus(200);
        $response->assertJsonStructure();
        $processCategoryJson = $response->json();
        $processCategory = ProcessCategory::where('uid', $processCategoryJson['cat_uid'])
            ->first();
        $this->assertNotNull($processCategory);
        $this->assertEquals($processCategory->uid, $processCategoryJson['cat_uid']);
        $this->assertEquals($processCategory->name, $data['name']);

        //Validate required cat_name
        $response = $this->api('PUT', self::API_TEST_CATEGORY . $catUid, []);
        $response->assertStatus(422);
        $this->assertEquals(
            __('validation.required', ['attribute' => 'name']), $response->json()['error']['message']
        );

        //Validate 404 if category does not exists
        $response = $this->api('PUT', self::API_TEST_CATEGORY . 'DOES_NOT_EXISTS', $data);
        $response->assertStatus(404);

        //Validate that category name is unique
        $data = [
            "name" => $processCategoryExisting->name,
        ];
        $response = $this->api('PUT', self::API_TEST_CATEGORY . $catUid, $data);
        $response->assertStatus(422);
        $this->assertEquals(
            __('validation.unique', ['attribute' => 'name']), $response->json()['error']['message']
        );

        //Validate invalid large name
        $data = [
            "name" => $faker->sentence(100),
        ];
        $response = $this->api('PUT', self::API_TEST_CATEGORY . $catUid, $data);
        $response->assertStatus(422);
        $this->assertEquals(
            __('validation.max.string', ['attribute' => 'name', 'max' => 100]), $response->json()['error']['message']
        );
    }

    /**
     * Test the deletion of process categories.
     */
    public function testDeleteProcessCategory()
    {
        $this->login();

        $processCategory = factory(ProcessCategory::class)->create();
        $catUid = $processCategory->uid;

        $response = $this->api('DELETE', self::API_TEST_CATEGORY . $catUid);
        $response->assertStatus(204);

        //Validate 404 if category does not exists
        $response = $this->api('DELETE', self::API_TEST_CATEGORY . 'DOES_NOT_EXISTS');
        $response->assertStatus(404);

        //Validate to do not delete category with processes
        $processCategory = factory(ProcessCategory::class)->create();
        $catUid = $processCategory->uid;
        factory(Process::class)->create([
            'process_category_id' => $processCategory->id
        ]);

    }

    /**
     * Test the show of process categories.
     */
    public function testShowProcessCategory()
    {
        $this->login();

        $processCategory = factory(ProcessCategory::class)->create();
        $catUid = $processCategory->uid;

        $response = $this->api('GET', self::API_TEST_CATEGORY . $catUid);
        $response->assertStatus(200);
        $response->assertJsonStructure();
        $processCategoryJson = $response->json();
        $this->assertEquals($processCategory->uid, $processCategoryJson['cat_uid']);
        $this->assertEquals($processCategory->name, $processCategoryJson['cat_name']);

        //Validate 404 if category does not exists
        $response = $this->api('GET', self::API_TEST_CATEGORY . 'DOES_NOT_EXISTS');
        $response->assertStatus(404);
    }
}
