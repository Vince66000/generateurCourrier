<?php


namespace App\Controller;


use Doctrine\DBAL\Driver\PDOConnection;
use Spipu\Html2Pdf\Html2Pdf;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class emargementController extends AbstractController
{


//    const dbLocal = "aeb_dol";
//    const usrLocal = "aeb.dol";
//    const mdpLocal = 'DmPzTs61NzG4';


    const dbLocal = "dolibarr";
    const usrLocal = "vincent";
    const mdpLocal = 'root';

    public function connecteur()
    {
        return $db = new PDOConnection('mysql:host=localhost;dbname='.self::dbLocal.'', ''.self::usrLocal.'', ''.self::mdpLocal.'');
    }

    /**
     *
     * @Route("emargement1", name="emargement1")
     */
    public function selectAff()
    {
        return $this->render('emargement/emargement1.html.twig');
    }

    /**
     * @Route("emargement2", name="emargement2")
     */
    public function getInfos()
    {

        $ref = '%' . $_POST['affaire'] . '%';

        $db = new PDOConnection('mysql:host=localhost;dbname=' . self::dbLocal . '', '' . self::usrLocal . '', '' . self::mdpLocal . '');
        $getInfos = $db->prepare('select                    
                        llx_projet.ref,
                        llx_projet_extrafields.user_suivi,
                        llx_projet_extrafields.reunions,
                        llx_projet_extrafields.lieuaff
                        from llx_projet
                        inner join llx_projet_extrafields on llx_projet.rowid = llx_projet_extrafields.fk_object 
                        where llx_projet.ref like :affaire');
        $getInfos->bindParam(':affaire', $ref);
        $getInfos->execute();

        $infos = $getInfos->fetch();
        $elemReunions = explode("-",$infos['reunions']);

        $elelReu2 = max($elemReunions);
//        var_dump($elelReu2);
        $elelReu3 = explode(" ", $elelReu2);
        $reunion = $elelReu3[0] ." ". $elelReu3[1] ." ". $elelReu3[2] ." " . $elelReu3[3] ." " . $elelReu3[4] ." " . $elelReu3[6];
        $affaire = $infos['ref'];
        $expert = $infos['user_suivi'];
        $lieu = $infos['lieuaff'];


        $template =  $this->renderView('emargement/emargement.html.twig', [
            'reunion' => $reunion,
            'affaire' => $affaire,
            'expert' => $expert,
            'lieu' => $lieu
        ]);


        $html2pdf = new html2pdf('P', 'A4', 'fr', true, 'utf-8', 10);
        $html2pdf->setTestTdInOnePage(false);
        $html2pdf->writeHTML($template);

        return $html2pdf->output('emargement_'. $affaire .'.pdf');


    }

}