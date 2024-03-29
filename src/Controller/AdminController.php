<?php

namespace App\Controller;

use App\Listener\ImageCacheSubscriber;
use App\Entity\Information;
use App\Form\InformationType;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\InformationRepository;
use Knp\Component\Pager\PaginatorInterface;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

class AdminController extends AbstractController
{
    /**
     * @Route("/admin", name="admin")
     */
    public function adminInfo(InformationRepository $repository, PaginatorInterface $paginatorInterface, Request $request)
    {
        $information = new Information();
        $informations = $paginatorInterface->paginate(
            $repository->findAllWithPagination($information),
            $request->query->getInt('page', 1), /*page number*/
            6 /*limit per page*/
        );
        return $this->render('admin/adminInfo.html.twig', [
            'informations' => $informations,
        ]);
    }

    /**
     * @Route("/admin/creation", name="creationInfo")
     * @Route("/admin/info/{id}", name="admin_modif", methods="GET|POST")
     */
    public function adminModif(Information $information = null, Request $request, EntityManagerInterface $entityManager,CacheManager $cacheManager, UploaderHelper $uploaderHelper)
    {   
        if(!$information)
        {
            $information = new Information();
        }

        $form = $this->createForm(InformationType ::class, $information);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid())
        {

        if($information->getImageFile()instanceof UploadedFile){
            $cacheManager->remove($uploaderHelper->asset($information, 'imageFile'));
        }
            $entityManager->persist($information);
            $entityManager->flush();
            $this->addFlash('success', "L'action a été effectué");
            return $this->redirectToRoute("admin");
        }

        return $this->render('admin/adminModif.html.twig', [
            'information' => $information,
            'form' => $form->createView(),
            "isModification" => $information->getId() !== null
        ]);
    }

    /**
     * @Route("/admin/{id}", name="supInfo", methods="SUP")
     */
    public function suppression(Information $information, Request $request, EntityManagerInterface $entityManager)
    {
        if($this->isCsrfTokenValid("SUP".$information->getId(), $request->get("_token"))){
            $entityManager->remove($information);
            $entityManager->flush();
            $this->addFlash('success', "L'action a été effectué");
            return $this->redirectToRoute("admin");

        }
    }
}
