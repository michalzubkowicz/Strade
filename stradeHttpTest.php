<?

include('stradeTest.php');

class stradeHttpTest extends stradeTest {

    protected function setUp()
    {
        $this->strade = new StradeHttp('123');
    }
}
