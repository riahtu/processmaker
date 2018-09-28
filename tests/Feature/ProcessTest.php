<?php
namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\Feature\Shared\RequestHelper;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessCategory;

class ProcessTest extends TestCase
{
    use DatabaseTransactions;
    use RequestHelper;

    // protected function setUp()
    // {
    //     parent::setUp();
    //     $this->user = factory(User::class)->create();
    // }

    public function testIndex() {  
        factory(Process::class)->create(['name'=>'Test Process']);
        factory(Process::class)->create(['name'=>'Another Process']);
        // factory(ProcessCategory::class)->create(['name'=>'Test Category']);
        $response = $this->webGet('/processes');
        $response->assertStatus(200);
        $response->assertViewIs('processes.index');
        $response->assertSee('Test Process');
        $response->assertSee('Another Process');
    }

    public function testEdit()
    {
      $process = factory(Process::class)->create(['name'=>'Test Edit']);
      $response = $this->webGet('processes/'.$process->uuid_text . '/edit');
      $response->assertStatus(200);
      $response->assertViewIs('processes.edit');
      $response->assertSee('Test Edit');
    }

    public function testCreate()
    {
        $process = factory(Process::class)->create(['name'=>'Test Create']); 
        $response = $this->webGet('/processes/create');
        $response->assertViewIs('processes.create');
        $response->assertStatus(200);  
    }
    public function testStore()
    {
        $response = $this->apiCall('POST' ,'/processes', [
            'name' => 'Stored new user',
            'description' => 'descript',
            'status' => 'ACTIVE'
            ]);
        
        $response->assertStatus(302);
        $this->assertDatabaseHas('processes', ['name' => 'Stored new user']);  // how do I verify DB table name?
        
    }
    public function testShow() 
    {
        $process = factory(Process::class)->create(['name'=>'Test show']); 
        $response = $this->webGet('processes/'.$process->uuid_text.'' );
        $response->assertViewIs('processes.show');
        $response->assertStatus(200);
        $response->assertSee('Test show');
    }
    // public function testUdate()
    // {
    //     $response = $this->apiCall('POST' ,'/processes'.$process->uuid_text.'/edit', ['name' => 'Stored new user']);
    // }
    // public function testDestroy()
    // {
    //     $process = factory(Process::class)->create(['name'=>'Test delete']);
    //     $response->assertRedirect('processes.index')
    // }
}
