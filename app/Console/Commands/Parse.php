<?php

namespace App\Console\Commands;

use App\Models\Currency;
use Illuminate\Console\Command;

class Parse extends Command {
    private $data = [];
    private $arrCurrencies = [];
    protected $signature = 'parse';
    protected $description = 'Create Parse operations';

    public function __construct(){
        parent::__construct();
    }

    public function handle(){
        Currency::query()->delete();
        $this->parse();
    }

    public function parse(){
        $data = file_get_contents("http://www.cbr.ru/scripts/XML_val.asp");
        $xml = new \SimpleXMLElement($data);
        $this->setNames($xml);

        $currensies = file_get_contents("http://www.cbr.ru/scripts/XML_daily.asp");
        $xmlCurrency = new \SimpleXMLElement($currensies);
        $this->setCurrency($xmlCurrency);

        foreach($this->arrCurrencies as $arrCurrency){
            Currency::create($arrCurrency);
        }



    }

    private function setNames($xml){
        foreach($xml as $item){
            $name = (string)$item->Name;
            $engName = (string)$item->EngName;
            $code = (string)trim($item->ParentCode);
            $this->data[$code] = ['name' => $name, 'english_name' => $engName];
        }
    }

    private function setCurrency($xml){
        $currensies = file_get_contents("http://www.cbr.ru/scripts/XML_daily.asp");
        $xmlCurrency = new \SimpleXMLElement($currensies);

        foreach($xmlCurrency->Valute as $item){
            $code = (string)$item->attributes()->ID;

            if(array_key_exists($code, $this->data)){
                $rate = (string)$item->Value;
                $charCode = (string)$item->CharCode;
                $digCode = (int)$item->NumCode;

                $this->arrCurrencies[] = $this->data[$code] + [
                        'rate' => str_replace(",", ".", $rate), 'digit_code' => $digCode, 'alphabetic_code' =>
                            $charCode,
                    ];
            }

        }

    }

}
