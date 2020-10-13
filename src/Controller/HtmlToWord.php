<?php


namespace App\Controller;


use Doctrine\DBAL\Driver\PDOConnection;
use PhpOffice\PhpWord\PhpWord;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


class HtmlToWord extends AbstractController
{

    public function test() {

        $ref =  '%' .$_POST['affaire'] . '%';
        $date = $_POST['datepicker'];
        $heure = $_POST['timepicker'];
        $civ = $_POST['civilite'];
        $expert = $_POST['expert'];
        $texteLibre = $_POST['textelibre'];
        $client = 'Client(e)';

        /**
         * requête qui récupère les coordonées d'un client lié à une affaire particulière
         */
        $db = new PDOConnection('mysql:host=localhost;dbname='.self::dbLocal.'', ''.self::usrLocal.'', ''.self::mdpLocal.'');
        $getSoc = $db->prepare ('select 
                        llx_projet.ref,
                        llx_socpeople.civility,
                        llx_socpeople.lastname, 
                        llx_socpeople.firstname, 
                        llx_socpeople.address,
                        llx_socpeople.zip,
                        llx_socpeople.town,
                        llx_projet_extrafields.lieuaff,
                        llx_c_type_contact.libelle
                        from llx_element_contact 
                        inner join llx_projet on llx_element_contact.element_id = llx_projet.rowid 
                        INNER JOIN llx_projet_extrafields ON llx_projet.rowid = llx_projet_extrafields.fk_object
                        inner join llx_socpeople on llx_element_contact.fk_socpeople = llx_socpeople.rowid 
                        inner join llx_c_type_contact on llx_element_contact.fk_c_type_contact = llx_c_type_contact.rowid
                        where llx_element_contact.element_id = ( select rowid from llx_projet where ref like :ref ) 
                        and fk_c_type_contact != 160 
                        and llx_c_type_contact.libelle like :client');
        $getSoc->bindParam(':ref', $ref);
        $getSoc->bindParam(':client', $client);

        $getSoc->execute();
        $societe = $getSoc->fetch();
        $refAff = $societe['ref'];

        //L'adresse d'expertise contenant des caractères en trop, je l'explose et exploite seulement les fragments dont j'ai besoin
        $lieuxExp = $societe['lieuaff'];
        $lieuxExp2 = explode(" ", $lieuxExp);
        $count = count($lieuxExp2);

        $lieuxExp3 = '';
        for($i = 3; $i < $count  ; $i++) {
            $lieuxExp3 .=  ' ' . $lieuxExp2[$i] . ' ';

        }


        $template = $this->renderView('convocations/modelConvoc.html.twig', [
            'affaire' => $ref,
            'societe' => $societe,
            'dateRdv' => $date,
            'heureRdv' => $heure,
            'civ' => $civ,
            'expert' => $expert,
            'textelibre' => $texteLibre,
            'lieuxexp' => utf8_encode($lieuxExp3)

        ]);





        $phpWord = new PhpWord();
        $section = $phpWord->addSection();
        \PhpOffice\PhpWord\Shared\Html::addHtml($section, $template, false, false);
        $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save('helloWorld.docx');


    }



}