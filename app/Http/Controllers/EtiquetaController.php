<?php

namespace App\Http\Controllers;

use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriver;
use Facebook\WebDriver\WebDriverBy;
use Illuminate\Http\Request;
use App\Models\Valores;
use Facebook\WebDriver\Remote\LocalFileDetector;
use Facebook\WebDriver\WebDriverCheckboxes;
use Facebook\WebDriver\WebDriverRadios;
use Facebook\WebDriver\WebDriverSelect;
use Illuminate\Support\Facades\Storage;
use Rap2hpoutre\FastExcel\Facades\FastExcel;
use Illuminate\Support\Collection;
use Smalot\PdfParser\Parser;

class EtiquetaController extends Controller
{
    public function index()
    {
        // Firefox
        $driver = RemoteWebDriver::create(env('SELENIUM_HOST'), DesiredCapabilities::firefox());
        // Vá para URL
        $driver->get(env('BEECARE_URL_TABLE'));

        //$page = $driver->findElement(WebDriverBy::xpath('//a[1]'))->getAttribute('href');
        $page = $driver->findElement(WebDriverBy::xpath('//a[1]'))->getText();
        $title = $driver->findElement(WebDriverBy::xpath('//h1[1]'))->getText();
        $subtitle = $driver->findElement(WebDriverBy::xpath('//div[@class="explanation"]//p[1]'))->getText();
        $informationTable = $driver->findElement(WebDriverBy::xpath('//table[1]//caption[1]'))->getText();

        $table = $driver->findElement(WebDriverBy::id('mytable'));
        $coluna = $table->findElements(WebDriverBy::tagName('td'));

        for ($index = 0; $index < count($coluna); $index = $index + 2) {
            $name = $coluna[$index]->getText();
            $amount = $coluna[$index + 1]->getText();

            try {
                Valores::create([
                    'page' => $page,
                    'title' => $title,
                    'subtitle' => $subtitle,
                    'informationTable' => $informationTable,
                    'name' => $name,
                    'amount' => $amount
                ]);
            } catch (\Exception $message) {
                return $message->getMessage();
            }
        }
        $driver->quit();
        $valores = Valores::all();
        return response()->json(['Dados' => $valores]);
    }

    public function setForm()
    {
        // Firefox
        $driver = RemoteWebDriver::create(env('SELENIUM_HOST'), DesiredCapabilities::firefox());
        // Vá para URL
        $driver->get(env('BEECARE_URL_FORM'));

        $driver->findElement(WebDriverBy::xpath('//input[@name="username"]'))->sendKeys('Gabriel Aires');
        $driver->findElement(WebDriverBy::xpath('//input[@name="password"]'))->sendKeys('1234');
        $driver->findElement(WebDriverBy::xpath('//textarea[@name="comments"]'))->sendKeys('Selenium with PHP');

        $path = Storage::path('file.pdf');

        $driver->findElement(WebDriverBy::xpath('//input[@name="filename"]'))
            ->setFileDetector(new LocalFileDetector())
            ->sendKeys($path);

        $checkBoxes = $driver->findElement(WebDriverBy::xpath('//input[@name="checkboxes[]"]'));
        $setCheckBox = new WebDriverCheckboxes($checkBoxes);
        $setCheckBox->selectByValue('cb2');

        $driver->findElement(WebDriverBy::cssSelector('input[type="radio"][value="rd1"]'))
            ->click();

        $radio = $driver->findElement(WebDriverBy::name('radioval'));
        $radioBox = new WebDriverRadios($radio);
        $radioBox->selectByValue('rd2');

        $dropdownInput = $driver->findElement(WebDriverBy::name('dropdown'));
        $dropdown = new WebDriverSelect($dropdownInput);
        $dropdown->selectByValue('dd2');

        $driver->findElement(WebDriverBy::xpath('//*[@id="HTMLFormElements"]/table/tbody/tr[9]/td/input[2]'))->click();

        $textPost = $driver->findElement(WebDriverBy::xpath('//h1[1]'))->getText();
        if ($textPost == "Processed Form Details") {
            $json = [
                'status' => 200,
                'message' => 'Sucesso'
            ];
        } else {
            $json = [
                'status' => 400,
                'message' => 'Erro, não foi possível realizar o procedimento!'
            ];
            redirect(env('BEECARE_URL_UP'));
        }

        $driver->quit();

        return response()->json($json);
    }

    public function download()
    {
        // Firefox
        $driver = RemoteWebDriver::create(env('SELENIUM_HOST'), DesiredCapabilities::firefox());
        // Vá para URL
        $driver->get(env('BEECARE_URL_DOWN'));
        $link = $driver->findElement(WebDriverBy::xpath('//a[@id="direct-download-a"]'))->getDomProperty('href');
        $getContents = file_get_contents($link);
        Storage::disk('local')->put('Teste TKS.txt', $getContents);
    }

    public function upload()
    {
        // Firefox
        $driver = RemoteWebDriver::create(env('SELENIUM_HOST'), DesiredCapabilities::firefox());
        // Vá para URL
        $driver->get(env('BEECARE_URL_UP'));

        $filePath = Storage::path('Teste TKS.txt');

        $driver->findElement(WebDriverBy::id('fileinput'))
            ->setFileDetector(new LocalFileDetector())
            ->sendKeys($filePath);

        $driver->findElement(WebDriverBy::id('itsafile'))->click();
        $driver->findElement(WebDriverBy::name('upload'))->click();

        $textPost = $driver->findElement(WebDriverBy::xpath('//h1[1]'))->getText();
        if ($textPost == "Uploaded File") {
            $json = [
                'status' => 200,
                'message' => 'Sucesso'
            ];
        } else {
            $json = [
                'status' => 400,
                'message' => 'Erro, não foi possível realizar o procedimento!'
            ];
            redirect(env('BEECARE_URL_UP'));
        }

        $driver->quit();

        return response()->json($json);
    }

    public function leitura()
    {
        // Firefox
        $driver = RemoteWebDriver::create(env('SELENIUM_HOST'), DesiredCapabilities::firefox());
        // Vá para URL
        $driver->get(env('BEECARE_URL_UP'));

        $pdfParser = new Parser();
        $pdfPath = Storage::path('LeituraPDF.pdf');
        $pdfParsePath = $pdfParser->parseFile($pdfPath);
        $paginas = $pdfParsePath->getPages();
        $file = fopen(Storage::path('LeituraPDF.csv'), 'w+');
        foreach ($paginas as $key => $pagina) {
            if ($key == 0) {
                $conteudo = $pagina->getText();
                $conteudo = preg_replace("/\r|\n/", "", $conteudo);

                preg_match_all('/ANS([0-9]*)/m', $conteudo, $ansMatches);
                $registroAns = $ansMatches[1][0] ?? "";
                preg_match_all('/Nome da Operadora([A-Z ]* [\w\/]*)/m', $conteudo, $operadoraMatches);
                $nomeOperadora = $operadoraMatches[1][0] ?? "";
                preg_match_all('/Código na Operadora([0-9]*)/m', $conteudo, $codOperadoraMatches);
                $codOperadora = $codOperadoraMatches[1][0] ?? "";
                preg_match_all('/Código na Operadora([0-9 ]*- [A-Z .-]*[0-9]*)/m', $conteudo, $nomeContratadoMatches);
                $nomeContratado = $nomeContratadoMatches[1][0] ?? "";
                preg_match_all('/Código CNES([0-9]{1})/m', $conteudo, $numloteMatches);
                $numlote = $numloteMatches[1][0] ?? "";
                preg_match_all('/Número do Lote([0-9]{7})/m', $conteudo, $numProtocoloMatches);
                $numProtocolo = $numProtocoloMatches[1][0] ?? "";
                preg_match_all('/Nº do Protocolo \(Processo\)([0-9\/]*)/m', $conteudo, $dataProtocoloMatches);
                $dataProtocolo = $dataProtocoloMatches[1][0] ?? "";
                preg_match_all('/Código da Glosa do Protocolo()/m', $conteudo, $codGlosaProtocoloMatches);
                $codGlosaProtocolo = $codGlosaProtocoloMatches[1][0] ?? "";

                preg_match_all('/Valor Informado do Protocolo (\([A-Z$\) .,0-9]{13})/m', $conteudo, $valorProtocoloMatches);
                $valorInformadoProtocolo = $valorProtocoloMatches[1][0] ?? "";
                $valorProcessadoProtocolo = $valorProtocoloMatches[1][0] ?? "";
                $valorLiberadoProtocolo = $valorProtocoloMatches[1][0] ?? "";

                preg_match_all('/Valor Informado do Protocolo (\([A-Z$\) .,0-9]{13})([A-Z$\) .,0-9]{11})([0-9.,]*)/m', $conteudo, $valorMatches);
                $valorGlosaProtocolo = $valorMatches[3][0] ?? "";
                $valorGlosaGeral = $valorMatches[3][0] ?? "";

                preg_match_all('/Valor Informado Geral (\([A-Z$\) .,0-9]{13})/m', $conteudo, $valorGeralmatches);
                $valorInformadoGeral = $valorGeralmatches[1][0] ?? "";
                $valorProcessadoGeral = $valorGeralmatches[1][0] ?? "";
                $valorLiberadoGeral = $valorGeralmatches[1][0] ?? "";

                $cabecalho = array(
                    "Registro ANS",
                    "Nome da Operadora",
                    "Código na Operadora",
                    "Nome do Contratado",
                    "Número do Lote",
                    "Número do Protocolo",
                    "Data do Protocolo",
                    "Código  da Glosa do Protocolo",
                    "Valor Informado do Protocolo",
                    "Valor Processado do Protocolo",
                    "Valor Liberado do Protocolo",
                    "Valor Glosa do Protocolo",
                    "Valor Informado Geral",
                    "Valor Processado Geral",
                    "Valor Liberado Geral",
                    "Valor Glosa Geral",
                    'Número da Guia no Prestador',
                    'Número da Guia Atribuido pela Operadora',
                    'Senha',
                    'Nome Beneficiario',
                    'Número da Carteira',
                    'Data  do Faturamento',
                    'Data Fim Faturamento',
                    'Hora Inicio do Faturamento',
                    'Hora Fim do Faturamento',
                    'Código da Glosa da Guia',
                    'Data de Realização',
                    'Tabela',
                    'Código do Procedimento',
                    'Descrição',
                    'Grau de Participação',
                    'Valor Informado',
                    'Quant. Executada',
                    'Valor Processado',
                    'Valor Liberado',
                    'Valor Glosa',
                    'Código da Glosa',
                    'Valor Informado da Guia',
                    'Valor Processado da Guia',
                    'Valor Liberado da Guia',
                    'Valor Glosa da Guia'
                );
                $data = array(
                    $registroAns,
                    $nomeOperadora,
                    $codOperadora,
                    $nomeContratado,
                    $numlote,
                    $numProtocolo,
                    $dataProtocolo,
                    $codGlosaProtocolo,
                    $valorInformadoProtocolo,
                    $valorProcessadoProtocolo,
                    $valorLiberadoProtocolo,
                    $valorGlosaProtocolo,
                    $valorInformadoGeral,
                    $valorProcessadoGeral,
                    $valorLiberadoGeral,
                    $valorGlosaGeral
                );
                fputcsv($file, $cabecalho);
                fputcsv($file, $data);
            }  else if ($key >= 2) {
                $conteudo = $pagina->getText();

                preg_match_all('/ANS([0-9]*)/m', $conteudo, $ansMatches);
                $registroAns = $ansMatches[1][0] ?? "";
                preg_match_all('/Nome da Operadora([A-Z ]* [\w\/]*)/m', $conteudo, $operadoraMatches);
                $nomeOperadora = $operadoraMatches[1][0] ?? "";
                preg_match_all('/Código na Operadora([0-9]*)/m', $conteudo, $codOperadoraMatches);
                $codOperadora = $codOperadoraMatches[1][0] ?? "";
                preg_match_all('/Código na Operadora([0-9 ]*- [A-Z .-]*[0-9]*)/m', $conteudo, $nomeContratadoMatches);
                $nomeContratado = $nomeContratadoMatches[1][0] ?? "";
                preg_match_all('/Código CNES([0-9]{1})/m', $conteudo, $numloteMatches);
                $numlote = $numloteMatches[1][0] ?? "";
                preg_match_all('/Número do Lote([0-9]{7})/m', $conteudo, $numProtocoloMatches);
                $numProtocolo = $numProtocoloMatches[1][0] ?? "";
                preg_match_all('/Nº do Protocolo \(Processo\)([0-9\/]*)/m', $conteudo, $dataProtocoloMatches);
                $dataProtocolo = $dataProtocoloMatches[1][0] ?? "";
                preg_match_all('/Código da Glosa do Protocolo()/m', $conteudo, $codGlosaProtocoloMatches);
                $codGlosaProtocolo = $codGlosaProtocoloMatches[1][0] ?? "";

                preg_match_all('/Valor Informado do Protocolo (\([A-Z$\) .,0-9]{13})/m', $conteudo, $valorProtocoloMatches);
                $valorInformadoProtocolo = $valorProtocoloMatches[1][0] ?? "";
                $valorProcessadoProtocolo = $valorProtocoloMatches[1][0] ?? "";
                $valorLiberadoProtocolo = $valorProtocoloMatches[1][0] ?? "";

                preg_match_all('/Valor Informado do Protocolo (\([A-Z$\) .,0-9]{13})([A-Z$\) .,0-9]{11})([0-9.,]*)/m', $conteudo, $valorMatches);
                $valorGlosaProtocolo = $valorMatches[3][0] ?? "";
                $valorGlosaGeral = $valorMatches[3][0] ?? "";

                preg_match_all('/Valor Informado Geral (\([A-Z$\) .,0-9]{13})/m', $conteudo, $valorGeralmatches);
                $valorInformadoGeral = $valorGeralmatches[1][0] ?? "";
                $valorProcessadoGeral = $valorGeralmatches[1][0] ?? "";
                $valorLiberadoGeral = $valorGeralmatches[1][0] ?? "";

                preg_match_all('/Número da Guia no Prestador([0-9]*)/m', $conteudo, $guiaPrestadorMatches);
                $numGuiaPrestador = $guiaPrestadorMatches[1][0] ?? "";

                preg_match_all('/Número da Guia no Prestador([0-9 -]{18})/m', $conteudo, $guiaOperadoramatches);
                $numGuiaOperadora = $guiaOperadoramatches[1][0] ?? "";

                preg_match_all('/Senha()/m', $conteudo, $senhaMatches);
                $senha = $senhaMatches[1][0] ?? "";

                preg_match_all('/Senha([A-Z0-9-  ]{40})/m', $conteudo, $numBeneficiarioMatches);
                $nomeBeneficiario = $numBeneficiarioMatches[1][0] ?? "";

                preg_match_all('/Nome do Beneficiário([0-9 ]{27})/m', $conteudo, $numCarteiraMatches);
                $numCarteira = $matches[1][0] ?? "";

                preg_match_all('/(Data Início do Faturamento[0-9\/]*)/m', $conteudo, $dataInicioFaturamentoMatches);
                $dataInicioFaturamento = $dataInicioFaturamentoMatches[1][0] ?? "";
                $dataInicioFaturamento = str_replace("Data Início do Faturamento", "", $dataInicioFaturamento);

                preg_match_all('/Data Início do Faturamento([0-9\/]*)/m', $conteudo, $dataFimFaturamentoMatches);
                $dataFimFaturamento = $dataFimFaturamentoMatches[1][0] ?? "";

                preg_match_all('/Hora Início do Faturamento([0-9:]*)/m', $conteudo, $horaInicioFaturamentoMatches);
                $horaInicioFaturamento = $horaInicioFaturamentoMatches[0][0] ?? "";
                $horaInicioFaturamento = str_replace("Hora Início do Faturamento", "", $horaInicioFaturamento);

                preg_match_all('/Hora Fim do Faturamento([0-9:]*)/m', $conteudo, $horaFimFaturamentoMatches);
                $horaFimFaturamento = $horaFimFaturamentoMatches[1][0] ?? "";

                preg_match_all('/Código da Glosa da Guia([0-9\/]*)/m', $conteudo, $codGlosaGuiaMatches);
                $codGlosaGuia = $codGlosaGuiaMatches[1][0] ?? "";

                preg_match_all('/Data de realização([0-9\/]*)/m', $conteudo, $dataRealizacaoMatches);
                $dataRealizacaoGuia = $dataRealizacaoMatches[1][0] ?? "";

                preg_match_all('/Tabela()/m', $conteudo, $tabelaMatches);
                $tabela = $tabelaMatches[1][0] ?? "";

                preg_match_all('/Código Procedimento()/m', $conteudo, $codProcedimentoMatches);
                $codProcedimento = $codProcedimentoMatches[1][0] ?? "";

                preg_match_all('/Descrição()/m', $conteudo, $descricaoMatches);
                $descricao = $descricaoMatches[1][0] ?? "";

                preg_match_all('/Grau de Participação()/m', $conteudo, $grauParticipacaoMatches);
                $grauParticipacao = $grauParticipacaoMatches[1][0] ?? "";

                preg_match_all('/Valor Informado()/m', $conteudo, $valorInformadoMatches);
                $valorInformado = $valorInformadoMatches[1][0] ?? "";

                preg_match_all('/Quant. Executada()/m', $conteudo, $qtdExecutadaMatches);
                $qtdExecutada = $qtdExecutadaMatches[1][0] ?? "";

                preg_match_all('/Valor Processado()/m', $conteudo, $valorProcessadoMatches);
                $valorProcessado = $valorProcessadoMatches[1][0] ?? "";

                preg_match_all('/Valor Liberado()/m', $conteudo, $valorLiberadoMatches);
                $valorLiberado = $valorLiberadoMatches[1][0] ?? "";

                preg_match_all('/Valor Glosa()/m', $conteudo, $valorGlosaMatches);
                $valorGlosa = $valorGlosaMatches[1][0] ?? "";

                preg_match_all('/Código da Glosa()/m', $conteudo, $codGlosaMatches);
                $codGlosa = $codGlosaMatches[1][0] ?? "";

                preg_match_all('/Valor Informado da Guia(\([A-Z$\) .,0-9]{13})/m', $conteudo, $valorInformadoGuiaMatches);
                $valorInformadoGuia = $valorInformadoGuiaMatches[1][0] ?? "";

                preg_match_all('/Valor Processado da Guia(\([A-Z$\) .,0-9]{13})/m', $conteudo, $valorProcessadoGuiaMatches);
                $valorProcessadoGuia = $valorProcessadoGuiaMatches[1][0] ?? "";

                preg_match_all('/Valor Liberado da Guia(\([A-Z$\) .,0-9]{13})/m', $conteudo, $valorLiberadoGuiaMatches);
                $valorLiberadoGuia = $valorLiberadoGuiaMatches[1][0] ?? "";

                preg_match_all('/Valor Glosa da Guia(\([A-Z$\) .,0-9]{13})/m', $conteudo, $valorGlosaGuiaMatches);
                $valorGlosaGuia = $valorGlosaGuiaMatches[1][0] ?? "";
                $data2[] = array(
                    $registroAns,
                    $nomeOperadora,
                    $codOperadora,
                    $nomeContratado,
                    $numlote,
                    $numProtocolo,
                    $dataProtocolo,
                    $codGlosaProtocolo,
                    $valorInformadoProtocolo,
                    $valorProcessadoProtocolo,
                    $valorLiberadoProtocolo,
                    $valorGlosaProtocolo,
                    $valorInformadoGeral,
                    $valorProcessadoGeral,
                    $valorLiberadoGeral,
                    $valorGlosaGeral,
                    $numGuiaPrestador,
                    $numGuiaOperadora,
                    $senha,
                    $nomeBeneficiario,
                    $numCarteira,
                    $dataInicioFaturamento,
                    $dataFimFaturamento,
                    $horaInicioFaturamento,
                    $horaFimFaturamento,
                    $codGlosaGuia,
                    $dataRealizacaoGuia,
                    $tabela,
                    $codProcedimento,
                    $descricao,
                    $grauParticipacao,
                    $valorInformado,
                    $qtdExecutada,
                    $valorProcessado,
                    $valorLiberado,
                    $valorGlosa,
                    $codGlosa,
                    $valorInformadoGuia,
                    $valorProcessadoGuia,
                    $valorLiberadoGuia,
                    $valorGlosaGuia
                );
                $dados2 = $data2;
            }
        }
        foreach($dados2 as $dado){
           fputcsv($file,$dado);
        }
        return response()->json([
            'status'=> 200,
            'message'=> 'Ok'
        ]);
    }
}
