<?php

namespace App\Controller;

use App\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Gesdinet\JWTRefreshTokenBundle\Request\RequestRefreshToken;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;

class AuthController extends ApiController
{
    /**
     * @Route("/register", name="register", methods={"POST"})
     */
    public function register(Request $request, UserPasswordEncoderInterface $encoder)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $request = $this->transformJsonBody($request);
        $password = $request->get('password');
        $email = $request->get('email');

        if (empty($password) || empty($email)){
            return $this->respondValidationError("Invalid Password or Email");
        }

        $user = new User();
        $user->setPassword($encoder->encodePassword($user, $password));
        $user->setEmail($email);
        $entityManager->persist($user);
        $entityManager->flush();
        return $this->respondWithSuccess(sprintf('User %s successfully created', $user->getEmail()));
    }

    /**
     * @Route("/api/logout", name="logout", methods={"POST"})
     * @param UserInterface $user
     * @param JWTTokenManagerInterface $JWTManager
     * @return JsonResponse
     */
    public function Logout(Request $request, RefreshTokenManagerInterface $refreshTokenManager)
    {
        $refreshTokenString = RequestRefreshToken::getRefreshToken($request, 'refresh_token');

        $refreshToken = $refreshTokenManager->get($refreshTokenString);

        if (null === $refreshToken) {
            return $this->respondValidationError("Refresh token does not exist");
        }

        $refreshTokenManager->delete($refreshToken);
        return new JsonResponse(['token' => 'deleted']);
    }
}
