<?php

class AdminModuleHandler {
    public function GET() {
        include_once ("templates/TheDataTank/header.php");
        $add_form = '
            <form name="Add Module" method="">
            <input type="hidden" name="/>
            </form>';
        //echo '<a id="add_module" href="/' . Config::$SUBDIR . '/"#">Add Module';
        include_once ("templates/TheDataTank/footer.php");
    }

    public function POST() {

    }

    public function PUT() { // update mod

    }

    public function DELETE() { // rm mod

    }
}

?>