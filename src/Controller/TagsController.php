<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Repository\NoteRepository;
use App\Repository\TagRepository;
use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Note;
use App\Entity\User;
use App\Entity\Tag;

class TagsController extends ApiController
{
    private function isEntity($id, $repository, UserInterface $user) 
    {
        if (!(is_int($id))) {     
            return ['success' => false, 'error' => 'Invalid id'];
        }  

        $ent = $repository->findById($user, $id);

        if ($ent === null) {
            return ['success' => false, 'error' => 'User does not have: ' . $tagId];
        }
        return ['success' => true, 'entity' => $ent];
    }

    /**
     * @Route("/api/set-tag", methods={"POST"})
     */
    public function setTag(
        Request $request, 
        UserInterface $user, 
        TagRepository $repository, 
        EntityManagerInterface $entityManager
    ) 
    {
        $request = $this->transformJsonBody($request);
        $body = $request->get('body');

        if (empty($body)) {
            return $this->respondError('Invalid body');
        }

        if ($repository->findByBody($user, $body) !== null) {
            return $this->respondError('This tag already exists');
        }
        
        $tag = new Tag();
        $tag->setBody($body)
            ->setUser($user)
        ;

        $entityManager->persist($tag);
        $entityManager->flush();
    
        return $this->respondWithSuccess(sprintf('Tag %s successfully created', $tag->getId()));
    }

    /**
     * @Route("/api/get-tags", methods={"POST"})
     */
    public function getTags(UserInterface $user, TagRepository $repository) 
    {
        $tagsObjects = $repository->findByUser($user);
        $tags = [];
        foreach ($tagsObjects as $key => $tag) {
            $tags[$key]['id'] = $tag->getId();
            $tags[$key]['body'] = $tag->getBody();
        }
        return new JsonResponse(['tags' => $tags]);
    }

    /**
     * @Route("/api/get-tag", methods={"POST"})
     */
    public function getTag(Request $request, TagRepository $repository, UserInterface $user) 
    {
        $request = $this->transformJsonBody($request);
        $result = $this->isEntity($request->get('tag'), $repository, $user);

        if (!$result['success']) {     
            return $this->respondError('Tag error: ' . $result['error']);
        }  

        $tag = ['id' => $result['entity']->getId(),
                'body' => $result['entity']->getBody(),
        ];

        return new JsonResponse(['tag' => $tag]);
    }

    /**
     * @Route("/api/del-tag", methods={"POST"})
     */
    public function delTag(
        Request $request, 
        TagRepository $repository, 
        EntityManagerInterface $entityManager,
        UserInterface $user) 
    {
        $request = $this->transformJsonBody($request);
        $result = $this->isEntity($request->get('tag'), $repository, $user);

        if (!$result['success']) {     
            return $this->respondError('Tag error: ' . $result['error']);
        } 

        $entityManager->remove($result['entity']);
        $entityManager->flush();

        return $this->respondWithSuccess(sprintf('Tag successfully deleted'));
    }

    /**
     * @Route("/api/del-tags", methods={"POST"})
     */
    public function delTags(UserInterface $user, TagRepository $repository) 
    {
        $del = $repository->delByUser($user);
        
        return $this->respondWithSuccess(sprintf('%s Tags successfully deleted', $del));
    }

    /**
     * @Route("/api/change-tag-body", methods={"POST"})
     */
    public function changeTagBody(
        Request $request, 
        TagRepository $repository, 
        EntityManagerInterface $entityManager,
        UserInterface $user) 
    {
        $request = $this->transformJsonBody($request);
        $result = $this->isEntity($request->get('tag'), $repository, $user);

        if (!$result['success']) {     
            return $this->respondError('Tag error: ' . $result['error']);
        }  

        $body = $request->get('body');
        if (empty($body)) {
            return $this->respondError('body is empty');
        }
        
        $result['entity']->setBody($body);
        $entityManager->flush();
        
        return $this->respondWithSuccess(sprintf('Tag successfully updated'));
    }
}
