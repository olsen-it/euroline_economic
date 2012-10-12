<?php
function put_in_economic($conf,$filen,$dir,$file) {
	$client = economic_connect($conf)
		or die("kan ikke forbinde til e-conomic");
	$total = 0;
	$fee = 0;
	foreach ($filen['rows'] as $row) {
		$row['Amount settled'] = floatval(str_replace(",",".",$row['Amount settled']));
		$total += $row['Amount settled'];
		createentry($client,date("Y-m-d",strtotime($row['Transaction date'])) . "T00:00:00",$row['Additional ref 1'],floatval(str_replace(",",".",-$row['Amount settled'])),$conf->eue_config->economic_settings->euroline_financeacc,null,7,$conf->eue_config->general->voucher_number,'FinanceVoucher','DKK',floatval(str_replace(",",".",-$row['Amount settled'])));
		echo "Amount settled: $total, ";
		$fee += (floatval($row['Amount settled']) / 100 * floatval($conf->eue_config->general->euroline_fee_percentage));
		echo "Fee: " . ($total / 100 * floatval($conf->eue_config->general->euroline_fee_percentage)) . "\n";
		}
		createentry($client,date("Y-m-d",strtotime($row['Transaction date'])) . "T00:00:00",$file,floatval($total-$fee),$conf->eue_config->economic_settings->euroline_debtorno,null,7,$conf->eue_config->general->voucher_number,'DebtorPayment','DKK',floatval($total-$fee));
		createentry($client,date("Y-m-d",strtotime($row['Transaction date'])) . "T00:00:00",$file,floatval($fee),$conf->eue_config->economic_settings->euroline_feeacc,null,7,$conf->eue_config->general->voucher_number,'FinanceVoucher','DKK',floatval($fee));
		system("mv \"$dir/$file\" \"$dir/Processed\"");
}
function economic_connect($conf) {
$url = 'https://www.e-conomic.com/secure/api1/EconomicWebservice.asmx?WSDL';
$client = new SoapClient($url, array('trace' => 1, 'exceptions' => 1)); 
   $client->Connect(array(
      'agreementNumber' => $conf->eue_config->economic_settings->agreement,
      'userName'        => $conf->eue_config->economic_settings->username,
      'password'        => $conf->eue_config->economic_settings->password));
   return $client;
}
function createcashbookentry_fromdata($conn = null, $data) {
    /*
     * Vi tjekker lige om $conn er sat, i såfald om det er en SoapClient
     */
    if ($conn === null || !$conn instanceof SoapClient) {
        echo "<h1>Vi skal bruge en gyldig forbindelse til e-conomic!</h1>";
        return false;
    }

    /*
     * Vi kører lige igennem $data, og fjerner evt. elementer der står null i.
     */
    $cleanData = array();
    foreach($data as $item => $value) {
        if (!is_null($value)) {
            $cleanData[$item] = $value;
        }
    }

    /*
     * Kør funktionen CashBookEntry_CreateFromData
     */
    try {
        $CashBookEntry = $conn->CashBookEntry_CreateFromData(array('data' => $cleanData));
        return $CashBookEntry->CashBookEntry_CreateFromDataResult;
    } catch (Exception $e) {
        var_dump($e);
        die();
    }
}
function datokonvertering_db_economic($dato) {
        $str = explode("-", $dato);
        return $str[2] . "-" . $str[1] . "-" . $str[0] . "T00:00:00";
}
function createentry($client,$dato,$tekst,$amount,$konto,$modkonto,$cbhandle,$vouchno,$type,$valuta,$amountincurrency) {
if ($type == "CreditorPayment") {
	$amount = $amount * -1;
	$amountincurrency = $amountincurrency * -1;
}
$data = array(
    "Type" => $type,                         # Definer hvilken type der er tale om, i dette eksempel er det et finansbilag (FinanceVoucher).
    "CashBookHandle" => array('Number' => $cbhandle),
    "DebtorHandle" => ($type == "DebtorPayment" ) ? array('Number' => $konto) : null, 
    "CreditorHandle" => ($type == "CreditorInvoice" || $type == "CreditorPayment") ?array('Number' => $konto) : null,
    "AccountHandle" => ($type == "FinanceVoucher") ? array('Number' => $konto) : null,
    "ContraAccountHandle" => ($modkonto != null) ? array('Number' => $modkonto) : null,   # Modkontoen.
    //"Date" => datokonvertering_db_economic($dato),
    "Date" => $dato,
    "VoucherNumber" => $vouchno,                               # Her sættes bilags nummeret, skal udfyldes, da e-conomics API ikke autonumere i denne funktion.
    "Text" => utf8_encode($tekst),
    "AmountDefaultCurrency" => $amount,
    "Amount" => $amountincurrency,
    "CurrencyHandle" => array('Code' => $valuta),         # Valuta koden
    "VatAccountHandle" => null,                         # Momskoden til "AccountHandle" kontoen. f.eks array('VatCode' => 'U25').
    "ContraVatAccountHandle" => null,
    "DebtorInvoiceNumber" => null,                      # Er "type" "DebtorPayment", så skal denne være udfyldt med: int(Faktura ID).
    "CreditorInvoiceNumber" => null,                    # Er "type" "CreditorInvoice" eller "CreditorPayment", så skal denne udfyldes med: string(Faktura ID).
    "DueDate" => null                                   # Er "type" "CreditorInvoice", så skal denne udfyldes med forfaldsdatoen, i samme type som "Date".
);
//if ($valuta == "DKK")
//	unset($data['AmountDefaultCurrency']);
if (createcashbookentry_fromdata($client,$data)) 
	return 1;
else 
	return 0;

}
?>
