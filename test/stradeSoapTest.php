<?

include('./stradeTest.php');

class stradeSoapTest extends stradeTest {

    protected function setUp()
    {
	//for test pass you need key
        $this->strade = new StradeSoap('123');
    }

}
