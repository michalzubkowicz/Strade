<?

include('stradeTest.php');

class stradeSoapTest extends stradeTest {

    protected function setUp()
    {
        $this->strade = new StradeSoap('123');
    }

}
