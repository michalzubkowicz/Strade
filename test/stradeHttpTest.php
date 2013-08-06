<?

include('./stradeTest.php');

class stradeHttpTest extends stradeTest {

    protected function setUp()
    {
	//for test pass you need key
        $this->strade = new StradeHttp('123');
    }
}
