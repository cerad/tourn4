<?php

namespace App\Ayso\DataTransformer;

//use Symfony\Component\Form\DataTransformerInterface;

use App\Ayso\AysoFinder;

class RegionToSarTransformer // implements DataTransformerInterface
{   
    /** @var  AysoFinder */
    private $finder;
    
    public function __construct(
        AysoFinder $finder
    )
    {
        $this->finder = $finder;
    }
    /** 
     * @param string $orgKey AYSOR:0894
     * @return string|null
     */
    public function transform($orgKey)
    {
        if (!$orgKey) return null;

        $org = $this->finder->findOrg($orgKey);
        if ($org) {
            $state = $org['state'] ? : '??';
            return $org['sar'] . '/' . $state;
        }
        return $org; // Unknown or invalid, maybe toss exception
    }
    /**
     * @param string $sar 5/C/0894
     * @return string|null
     */
    public function reverseTransform($sar)
    {
        if (!$sar) return null;
        
        $sarParts = explode('/',$sar);
        if (count($sarParts) < 3) {
            die('sar error: ' . $sar);
        }
        $region = (int)$sarParts[2];
        if ($region < 1 || $region > 9999) {
            die('sar region number error: ' . $sar);
        }
        return sprintf('AYSOR:%04d',$region);
    }
}
?>
