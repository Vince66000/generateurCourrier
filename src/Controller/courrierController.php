<?php

namespace App\Controller;

use Doctrine\DBAL\Driver\PDOConnection;
use PhpOffice\PhpWord\PhpWord;
use Spipu\Html2Pdf\Html2Pdf;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class courrierController extends AbstractController {


    //
//  const dbLocal = "aeb_dol";
//  const usrLocal = "aeb.dol";
//  const mdpLocal = 'DmPzTs61NzG4';

    const dbLocal = "dolibarr";
    const usrLocal = "vincent";
    const mdpLocal = 'root';





    /**
     * @return string
     * @Route("CourrierClientAssRec", name="assistanceReception")
     */
    public function formCourrCltAssRep() :Response
    {

        return $this->render('courriers/formCourrCltAssRep.html.twig');
    }

    /**
     * @return string
     * @Route ("generateCourrAssRec", name="generateCourrAssRec")
     */
    public function generCourr()
    {

        $ref =  '%' .$_POST['affaire'] . '%';
        $expert = $_POST['expert'];
        $client = 'Client(e)';
        $entreprise = 'Entreprise';
        $texteLibre = $_POST['desordres'];

        /**
         *  on récupère les coordonées du client
         */
        $db = new \PDO('mysql:host=localhost;dbname='.self::dbLocal.'', ''.self::usrLocal.'', ''.self::mdpLocal.'');
        $getClient = $db->prepare ('select
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
        $getClient->bindParam(':ref', $ref);
        $getClient->bindParam(':client', $client);

        $getClient->execute();
        $client = $getClient->fetch();

        /**
         *  .. puis celles de l'entreprise qui a fait les travaux
         */
        $getSoc = $db->prepare ('select
                        llx_socpeople.civility,
                        llx_socpeople.lastname,
                        llx_socpeople.firstname,
                        llx_socpeople.address,
                        llx_socpeople.zip,
                        llx_socpeople.town,
                        llx_c_type_contact.libelle
                        from llx_element_contact
                        inner join llx_projet on llx_element_contact.element_id = llx_projet.rowid
                        inner join llx_socpeople on llx_element_contact.fk_socpeople = llx_socpeople.rowid
                        inner join llx_c_type_contact on llx_element_contact.fk_c_type_contact = llx_c_type_contact.rowid
                        where llx_element_contact.element_id = ( select rowid from llx_projet where ref like :ref )
                        and fk_c_type_contact != 160
                        and llx_c_type_contact.libelle like :entreprise');
        $getSoc->bindParam(':ref', $ref);
        $getSoc->bindParam(':entreprise', $entreprise);

        $getSoc->execute();
        $societe = $getClient->fetch();

        $lieuxExp = $client['lieuaff'];
        $lieuxExp2 = explode(" ", $lieuxExp);
        $count = count($lieuxExp2);
        $lieuxExp3 = '';
        for($i = 3; $i < $count  ; $i++) {
            $lieuxExp3 .=  ' ' . $lieuxExp2[$i] . ' ';

        }

        $template =  $this->renderView('courriers/modelCourrierCltAssRep.html.twig', [
            'societe' =>$societe,
            'client' => $client,
            'expert' => $expert,
            'texteLibre' =>$texteLibre,
            'lieuxexp' => utf8_encode($lieuxExp3)
        ]);


        $html2pdf = new html2pdf('P', 'A4', 'fr', true, 'utf-8', 10);
        $html2pdf->setTestTdInOnePage(false);
        $html2pdf->writeHTML($template);

        return $html2pdf->output('convocationClt.pdf');


    }





}