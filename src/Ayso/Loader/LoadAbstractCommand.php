<?php

namespace App\Ayso\Loader;

use App\Ayso\AysoConnection;
use App\Ayso\DataTransformer\PhoneTransformer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Doctrine\DBAL\Statement;
use Doctrine\DBAL\Connection;

abstract class LoadAbstractCommand extends Command
{
    /** @var  Connection */
    protected $conn;

    /** @var PhoneTransformer  */
    protected $phoneTransformer;

    /** @var  Statement */
    protected $insertVolStmt;

    /** @var  Statement */
    protected $updateVolStmt;

    /** @var  Statement */
    protected $checkVolStmt;

    /** @var  Statement */
    protected $insertCertStmt;

    /** @var  Statement */
    protected $updateCertStmt;

    /** @var  Statement */
    protected $checkCertStmt;

    /** @var  Statement */
    protected $insertOrgStmt;

    /** @var  Statement */
    protected $checkOrgStmt;

    /** @var  Statement */
    protected $updateOrgStmt;

    /** @var  Statement */
    protected $insertOrgStateStmt;

    /** @var  Statement */
    protected $checkOrgStateStmt;

    public function __construct(AysoConnection $conn, PhoneTransformer $phoneTransformer)
    {
        parent::__construct();

        $this->conn = $conn;
        $this->phoneTransformer = $phoneTransformer;

        $this->initStatements($conn);

        $this->initCerts();
    }
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Start the processing
        $filename = $input->getArgument('filename');

        echo sprintf("Loading AYSO File: %s...\n",$filename);
        
        $this->load($filename);
    }
    abstract protected function load($filename);
    
    protected function initStatements(Connection $conn)
    {
        $sql = <<<EOD
INSERT INTO vols
(fedKey,name,email,phone,gender,sar,regYear)
VALUES (?,?,?,?,?,?,?)
EOD;
        $this->insertVolStmt = $conn->prepare($sql);

        $sql = <<<EOD
UPDATE vols SET 
  name = ?, email = ?, phone = ?, gender = ?, sar = ?, regYear = ?
WHERE fedKey = ?
EOD;
        $this->updateVolStmt = $conn->prepare($sql);

        $sql = 'SELECT * FROM vols WHERE fedKey = ?';
        $this->checkVolStmt = $conn->prepare($sql);

        $sql = 'INSERT INTO certs (fedKey,role,roleDate,badge,badgeDate) VALUES (?,?,?,?,?)';
        $this->insertCertStmt = $conn->prepare($sql);

        $sql = 'SELECT roleDate,badge,badgeDate FROM certs WHERE fedKey = ? AND role = ?';
        $this->checkCertStmt = $conn->prepare($sql);

        $sql = 'UPDATE certs SET roleDate = ?, badge = ?, badgeDate = ? WHERE fedKey = ? AND role = ?';
        $this->updateCertStmt = $conn->prepare($sql);

        $sql = 'SELECT state FROM orgs WHERE orgKey = ?';
        $this->checkOrgStmt = $conn->prepare($sql);

        $sql = 'INSERT INTO orgs (orgKey,sar,state) VALUES (?,?,?)';
        $this->insertOrgStmt = $conn->prepare($sql);

        $sql = 'UPDATE orgs SET state = ? WHERE orgKey = ?';
        $this->updateOrgStmt = $conn->prepare($sql);

        $sql = 'SELECT orgKey FROM orgStates WHERE orgKey = ? AND state = ?';
        $this->checkOrgStateStmt = $conn->prepare($sql);

        $sql = 'INSERT INTO orgStates (orgKey,state) VALUES (?,?)';
        $this->insertOrgStateStmt = $conn->prepare($sql);
    }
    protected function initCerts()
    {
        // Mess with badge list
        $badgeSorts = [];
        foreach($this->certMetas as $certMeta) {
            if ($certMeta['role']) {
                $badgeSorts[$certMeta['badge']] = $certMeta['sort'];
            }
        }
        $this->badgeSorts = $badgeSorts;
    }
    protected $certMetas = [
        'U-8 Official' => [
            'role'  => 'CERT_REFEREE',
            'badge' => 'U8',
            'sort'  =>  2,
        ],
        'Assistant Referee' => [
            'role'  => 'CERT_REFEREE',
            'badge' => 'Assistant',
            'sort'  =>  5,
        ],
        'Assistant Referee & Safe Haven Referee' => [ // from bsb
            'role'  => 'CERT_REFEREE',
            'badge' => 'Assistant',
            'sort'  =>  5,
        ],
        'Regional Referee' => [
            'role'  => 'CERT_REFEREE',
            'badge' => 'Regional',
            'sort'  => 10,
        ],
        'Regional Referee & Safe Haven Referee' => [ // from bsb
            'role'  => 'CERT_REFEREE',
            'badge' => 'Regional',
            'sort'  => 10,
        ],
        'Intermediate Referee' => [
            'role'  => 'CERT_REFEREE',
            'badge' => 'Intermediate',
            'sort'  => 20,
        ],
        'Advanced Referee' => [
            'role'  => 'CERT_REFEREE',
            'badge' => 'Advanced',
            'sort'  => 30,
        ],
        'National Referee' => [
            'role'  => 'CERT_REFEREE',
            'badge' => 'National',
            'sort'  => 90,
        ],
        'National 1 Referee' => [
            'role'  => 'CERT_REFEREE',
            'badge' => 'National_1',
            'sort'  => 80,
        ],
        'National 2 Referee' => [
            'role'  => 'CERT_REFEREE',
            'badge' => 'National_2',
            'sort'  => 70,
        ],
        'Z-Online AYSOs Safe Haven' => [
            'role'  => 'CERT_SAFE_HAVEN',
            'badge' => 'AYSO',
            'sort'  => 90,
        ],
        'AYSOs Safe Haven' => [
            'role'  => 'CERT_SAFE_HAVEN',
            'badge' => 'AYSO',
            'sort'  => 90,
        ],
        'Webinar-AYSOs Safe Haven' => [
            'role'  => 'CERT_SAFE_HAVEN',
            'badge' => 'AYSO',
            'sort'  => 90,
        ],
        'Z-Online Refugio Seguro de AYSO' => [
            'role'  => 'CERT_SAFE_HAVEN',
            'badge' => 'AYSO',
            'sort'  => 90,
        ],
        'Safe Haven Referee' => [
            'role'  => 'CERT_SAFE_HAVEN',
            'badge' => 'Referee',
            'sort'  => 70,
        ],
        'U-8 Official & Safe Haven Referee' => [ // from bsb
            'role'  => 'CERT_SAFE_HAVEN',
            'badge' => 'Referee',
            'sort'  => 70,
        ],
        'Z-Online Safe Haven Referee' => [
            'role'  => 'CERT_SAFE_HAVEN',
            'badge' => 'Referee',
            'sort'  => 70,
        ],
        'Safe Haven Coach' => [ // Added from bsb
            'role'  => 'CERT_SAFE_HAVEN',
            'badge' => 'Coach',
            'sort'  => 71,
        ],
        'Z-Online Safe Haven Coach' => [ // Added from bsb
            'role'  => 'CERT_SAFE_HAVEN',
            'badge' => 'Coach',
            'sort'  => 71,
        ],
        'Safe Haven Update' => [
            'role'  => null,
        ],
        'Webinar-Safe Haven Update' => [
            'role'  => null,
        ],
        'Z-Online CDC Concussion Awareness Training' => [
            'role'  => 'CERT_CONCUSSION',
            'badge' => 'CDC Concussion',
            'sort'  => 90,
        ],
        'CDC Online Concussion Awareness Training' => [
            'role'  => 'CERT_CONCUSSION',
            'badge' => 'CDC Concussion',
            'sort'  => 90,
        ],

        // From BSB dump
        'U-6 Coach'           => ['role'  => null,],
        'Z-Online U-6 Coach'  => ['role'  => null,],
        'U-8 Coach'           => ['role'  => null,],
        'Z-Online U-8 Coach'  => ['role'  => null,],
        'U-10 Coach'          => ['role'  => null,],
        'Z-Online U-10 Coach' => ['role'  => null,],
        'U-12 Coach'          => ['role'  => null,],
        'U-12 Coach - Cross Certification'          => ['role'  => null,],
        'Intermediate Coach'  => ['role'  => null,],
        'Intermediate Coach - Cross Certification'  => ['role'  => null,],
        'Advanced Coach'  => ['role'  => null,],
        'Advanced Coach - Cross Certification'  => ['role'  => null,],
        'National Coach'  => ['role'  => null,],

        'BASIC'                   => ['role'  => null,],
        'Z-Online BASIC'          => ['role'  => null,],
        'Dispute Resolution'      => ['role'  => null,],
        'Dispute Resolution REV'      => ['role'  => null,],
        'Introductory Management' => ['role'  => null,],
        'Regional Management'     => ['role'  => null,],
        'Registrar' => ['role'  => null,],
        'z-Online Regional Referee without Safe Haven' => ['role'  => null,],
        'RC and Board Orientation' => ['role'  => null,],
        'Coach Administrator Training' => ['role'  => null,],
        'Advanced Management' => ['role'  => null,],
        'CVPA' => ['role'  => null,],
        'Introduction to Instruction' => ['role'  => null,],
        'Management Instructor Course' => ['role'  => null,],
        'Referee Assignor-Scheduler' => ['role'  => null,],
        'Referee Instructor' => ['role'  => null,],
        'Referee Instructor Course' => ['role'  => null,],
        'Regional Board Member Training' => ['role'  => null,],
        'Regional Referee Administrator' => ['role'  => null,],
        'Webinar - Regional Referee Administrator' => ['role'  => null,],
        'Webinar - Safety Director' => ['role'  => null,],
        'Activity Leader' => ['role'  => null,],
        'Coach Instructor' => ['role'  => null,],
        'Intermediate Referee Course' => ['role'  => null,],
        'RC Training' => ['role'  => null,],
        'Webinar - CVPA' => ['role'  => null,],
        'Z-Online AYSO Summary of the Laws of the Game' => ['role'  => null,],
        'Webinar - CVPA Annual Update' => ['role'  => null,],
        'Safety Director' => ['role'  => null,],
        'Advanced Coach Instructor' => ['role'  => null,],
        'Due Process' => ['role'  => null,],
        'Financial Auditor' => ['role'  => null,],
        'Webinar - Division Coordinator' => ['role'  => null,],
        'Webinar - Registrar' => ['role'  => null,],
        'Webinar - Treasurer' => ['role'  => null,],
        'Z-Online Treasurer 1' => ['role'  => null,],
        'Advanced Referee Course' => ['role'  => null,],
        'Treasurer I' => ['role'  => null,],
        'Z-Online Regional Referee Course' => ['role'  => null,],
        'Z-Online Intermediate Coach Pre Course' => ['role'  => null,],
        'Division Coordinator' => ['role'  => null,],
        'Tournament Management Training' => ['role'  => null,],
        'Webinar -  Area Referee Administrator' => ['role'  => null,],
        'AD Training' => ['role'  => null,],
        'Advanced Management Instructor' => ['role'  => null,],
        'Basic Coach Instructor' => ['role'  => null,],
        'Basic Management Instructor' => ['role'  => null,],
        'Intermediate Management' => ['role'  => null,],
        'Management Instructor' => ['role'  => null,],
        'Registrar II' => ['role'  => null,],
        'VIP Instructor' => ['role'  => null,],
        'VIP Volunteer Training' => ['role'  => null,],
        'U-10 Field Session Only' => ['role'  => null,],
        'Basic Referee Instructor' => ['role'  => null,],
        'National Referee Course' => ['role'  => null,],
        'Treasurer II' => ['role'  => null,],
        'Advanced Referee Instructor' => ['role'  => null,],
        'Advanced Referee Instructor Course' => ['role'  => null,],
        'Referee Assessor' => ['role'  => null,],
        'Referee Instructor Evaluator' => ['role'  => null,],
        'UIP - Basic Instructor Course' => ['role'  => null,],
        'Referee Assessor Course' => ['role'  => null,],
        'Advanced Management Instructor Course' => ['role'  => null,],
        'Referee Instructor Evaluator Course' => ['role'  => null,],
        'VIP Buddy Training and Certification' => ['role'  => null,],
        'Webinar - BASIC' => ['role'  => null,],
        'CVPA Annual Update' => ['role'  => null,],
        'Safety Director II' => ['role'  => null,],
        'National Referee Assessor' => ['role'  => null,],
        'National Referee Assessor Course' => ['role'  => null,],
        'CVPA Instructor' => ['role'  => null,],
        'Section and Area Staff Training' => ['role'  => null,],
        'Grade5 Regional Teacher' => ['role'  => null,],
        'UIP - Intermediate Instructor Course' => ['role'  => null,],
        'Webinar - Referee - VIP - National Games 2014' => ['role'  => null,],
        'National Coaching Course' => ['role'  => null,],
        'Annual Instructor Update' => ['role'  => null,],
        'Webinar - Area and Section CVPA Training' => ['role'  => null,],
        'Basic Referee Instructor Course' => ['role'  => null,],
        'Coach Instructor Course' => ['role'  => null,],
        'Basic Coach Instructor Course' => ['role'  => null,],
        'Area Referee Administrator' => ['role'  => null,],
        'National Coach Instructor' => ['role'  => null,],
        'Annual Safe Haven Review' => ['role'  => null,],
        'VIP Coach/Referee' => ['role'  => null,],
        'Regional Referee online companion course' => ['role'  => null,],
        'Regional Referee Online Companion Course' => ['role'  => null,],
        'National Referee Instructor' => ['role'  => null,],
        'Advanced Coach Instructor Course' => ['role'  => null,],
        'National Management Instructor' => ['role'  => null,],
        'National Safe Haven Instructor' => ['role'  => null,],
        'Schoolyard' => ['role'  => null,],
        'AYSO Instructor Qualifications on Hold' => ['role'  => null,],
        'Z-Online Regional Referee' => ['role'  => null,],
        'Winayso' => ['role'  => null,],
        'EXTRA and Secondary Play Admin Training' => ['role'  => null,],
        'Webinar-Coach Administrator Training' => ['role'  => null,],
        'Grade4 Instructor' => ['role'  => null,],
        'eAYSO Instructor Training' => ['role'  => null,],
        'B Coach' => ['role'  => null,],
        'Area Staff Training' => ['role'  => null,],
        'Management Continuing Ed' => ['role'  => null,],
        'C Coach' => ['role'  => null,],
        'Advanced Instructor Trainer' => ['role'  => null,],
        'Ramp Administrative' => ['role'  => null,],
        'Z-Online Advanced Coach Pre Course' => ['role'  => null,],
        'Grade2 Coach Instructor' => ['role'  => null,],
        'z-online Annual Safe Haven Review' => ['role'  => null,],
        'Spanish - Intermediate Referee Course' => ['role'  => null,],
        'Referee Mentor' => ['role'  => null,],
        'National Instructor Trainer' => ['role'  => null,],
        'Grade3 Instructor' => ['role'  => null,],
        'Instructor Trainer' => ['role'  => null,],
        'Grade2 Instructor' => ['role'  => null,],
        'Grade2 Referee Instructor' => ['role'  => null,],
        'eAYSO Instructor' => ['role'  => null,],
        'Basic Management Instructor Course' => ['role'  => null,],
        //'' => ['role'  => null,],

    ];
    protected $badgeSorts = [];
}