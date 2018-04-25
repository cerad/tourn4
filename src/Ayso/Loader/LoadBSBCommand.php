<?php

namespace App\Ayso\Loader;

use Symfony\Component\Console\Input\InputArgument;

class LoadBSBCommand extends LoadAbstractCommand
{
    protected $vols = [];

    protected function configure()
    {
        $this->setName('ayso:load:bsb');
        $this->setDescription('Load AYSO from BSB.');
        $this->addArgument('filename', InputArgument::REQUIRED, 'Path to BSB file');
    }

    protected function load($filename)
    {
        $fp = fopen($filename, 'r');

        /** @noinspection PhpUnusedLocalVariableInspection */
        $header = fgetcsv($fp);
        //dump($header);

        $cnt = 0;

        while ($row = fgetcsv($fp)) {
            $this->processRow($row);
            $cnt++;
            if (($cnt % 100) === 0) {
                echo "Processing $cnt\r";
            }
        }
        echo "Processed  $cnt\n";
        /*
        foreach($this->names as $name) {
            echo sprintf("Changed %-20s TO %s\n",$name[0],$name[1]);
        }
        echo sprintf("Changed %d names\n",count($this->names));*/


        foreach($this->emails as $email) {
            echo sprintf("Changed %-30s TO %s\n",$email[0],$email[1]);
        }
        echo sprintf("Changed %d emails\n",count($this->emails));

        fclose($fp);
    }
    protected function processRow($row)
    {
        $item = new Item();
        $item->fedKey   = trim($row[0]);
        $item->regYear  = trim($row[1]);
        $item->fname    = trim($row[2]);
        $item->lname    = trim($row[3]);
        $item->phone    = trim($row[4]);
        $item->email    = trim($row[5]);
        $item->portal   = trim($row[8]);

        $cert     = trim($row[6]);
        $certDate = trim($row[7]);


        if (!$item->fedKey) {
            dump($item); die('Missing fed key');
        }
        $item->fedKey = 'AYSOV:' . $item->fedKey;

        if (!$item->regYear) {
            dump($item); die('Missing reg year');
        }
        $item->regYear = 'MY' . $item->regYear; // Maybe check the format later

        // Check vol info first time through
        if (!isset($this->vols[$item->fedKey])) {
            $this->processItemVol($item);
            $this->vols[$item->fedKey] = true;
        }
        // Process the cert
        $this->processCert($item->fedKey,$cert,$certDate);
    }
    protected function processCert($fedKey,$cert,$certDate)
    {
        if (!$cert) {
            return;  // Seems to just happen
        }
        if (!isset($this->certMetas[$cert])) {
            die("\n" . 'Cert ' . $cert . "\n");
        }
    }
    protected $names = [];
    protected $emails = [];
    protected function processItemVol(Item $item)
    {
        // Existing?
        $this->checkVolStmt->execute([$item->fedKey]);
        $vol = $this->checkVolStmt->fetch();

        if ($vol) { // New

            $updates = [];
            if ($item->regYear > $vol['regYear']) {
                $updates['regYear'] = $item->regYear;
            }
            $volPhone  = $this->phoneTransformer->reverseTransform($vol['phone']);
            $itemPhone = $this->phoneTransformer->reverseTransform($item->phone);
            if ($volPhone !== $itemPhone) {
                $updates['phone'] = $this->phoneTransformer->transform($itemPhone);
            }
            $itemName = $item->getName();
            if ($itemName !== $vol['name']) {
                $updates['name'] = $itemName;
                $this->names[] = [$vol['name'],$itemName];
                //dump($item);dump($vol); dump($updates); die('Name');
            }
            $itemEmail = strtolower($item->email);
            if ($itemEmail !== $vol['email']) {
                $updates['email'] = $itemEmail;
                $this->emails[] = [$vol['email'],$itemEmail];
            }
            if (count($updates)) {
                $this->conn->update('vols',$updates,['fedKey' => $item->fedKey]);
                //dump($item);dump($vol); dump($updates); die('Existing');
            }
            //dump($item);dump($vol); die('Existing');
            //$this->insertVolStmt->execute([$fedKey,$name,$email,$phone,$gender,$sar,$regYear]);
            //return;
        }
        // New

    }
}
class Item {
    public $fedKey;
    public $regYear;
    public $email;
    public $phone;
    public $fname,$lname;
    public $portal;

    public function getName()
    {
        // always case changes and what not
        $fname = strtolower($this->fname);
        $lname = strtolower($this->lname);
        return ucfirst($fname) . ' ' . ucfirst($lname);
    }
}
