
<?php
class LoginController {

    public function doLogin($email, $senha, $tipo, $loja = null, $social = false, $lembrar = false, $registerSession = true, $code2FA = null, $remoteAddr = null, $userAgent = null, $authToken = null, $novaSenha = null)
    {
        $usuario = null;
        $senhaValida = false;
        $msgErroAutenticacao = null;
        if (!$remoteAddr) {
            $remoteAddr = $this->getRequest()->getRemoteAddr();
        }
        if (!$userAgent) {
            $userAgent = $this->getRequest()->getUserAgent();
        }
        if ($tipo == Usuario::TIPO_WEBSERVICE) {
            $httpAuth = new Http\BasicHttpAuthentication();
            $httpAuth->setFunctionValidUser(function($token, $password) use (&$usuario, &$senhaValida, &$email) {
                if (strlen($token) == 64) {
                    $usuario = $this->getUsuarioRepository()->getUsuarioByToken($token, Model\Usuario::TIPO_WEBSERVICE);
                    if ($usuario) {
                        $senhaValida = $usuario->validaSenha($password);
                        $email = $token;
                        return $senhaValida;
                    }
                }
                return false;
            });
            $httpAuth->auth();
        } else if (empty($email) or ( empty($senha) and!$social)) {
            throw new LoginException('Usuário e senha são obrigatórios!');
        } else {
            //login Admin - temos que validar isso com o repositório central de usuáros
            if ($tipo == Usuario::TIPO_ADMINISTRATIVO and ControleCentralController::isEmailControladoCentral($email)) {
                /**
                 * #6555
                 * VAMOS VALIDAR NOSSO USUÁRIO NO CONTROLE CENTRAL, como sabemos se é
                 * 1 - Tipo do Usuário Master (valida dentro do método)
                 * 2 - Email com dominio @controle-central.com.br
                 */
                //se der erro no login o método
                try {
                    $controleCentral = new ControleCentralController();
                    $usuario = $controleCentral->auth($email, $senha, $remoteAddr);

                    if ($usuario !== null) {
                        $senhaValida = true;
                    }
                } catch (\Exception $exc) {
                    throw $exc;
                }
            }
            //se null controler-central não possui configuração ou não precisamos validar com ele
            if ($usuario === null) {
                // se fornecido token, considera login automático, consultando a partir deste
                if ($authToken) {
                    $tokenRepository = $this->getEntityManager()->getRepository('Model\UsuarioToken');
                    $authUserToken = $tokenRepository->findTokenValidoByHash($authToken);
                    // usuário possui token para cada browser autenticado
                    if (!$authUserToken || !$authUserToken->getUsuario() || $authUserToken->getUserAgent() !== $userAgent) {
                        throw new LoginException('Token usuário inválido para acesso!');
                    }
                    /* @var $usuario Usuario  */
                    $usuario = $authUserToken->getUsuario();
                } else {
                    /* @var $usuario Usuario  */
                    $usuario = $this->getUsuarioRepository()->getUsuarioByLogin($email, $tipo, $loja);
                }

                if ($usuario !== null) {
                    if ($social) {
                        $senhaValida = true;
                    } else {
                        //se estamos aqui e ainda é master esta errado deveria ter acessado pelo controler-central
                        if ($usuario->getTipo() == Usuario::TIPO_ADMINISTRATIVO_MASTER) {
                            $usuario->setTipo(Usuario::TIPO_ADMINISTRATIVO);
                            $this->getEntityManager()->persist($usuario);
                            $this->getEntityManager()->flush($usuario);
                        }
                        $senhaValida = $usuario->validaSenha($senha);
                        /* && $this->verificaRestricao($usuario, $loja); */
                        //acesso com senha gerada somente para o site
                        if (!$senhaValida && $tipo == Usuario::TIPO_ECOMMERCE) {
                            //vamos validar a senha do administrador para acesso dos atendentes na conta do cliente
                            if ($usuario->getPessoa()->geraSenhaAcessoAdministrador() == $senha) {
                                $senhaValida = true;
                            }
                        }
                    }
                }
            }

            $msgErroAutenticacao = 'Usuário ou senha informados inválidos!';

            //se a senha é valida vamos verificar se existe restrição de hosts para este usuário
            if ($senhaValida && $usuario->getTipoAuth2FA() !== Usuario::TIPO_DOIS_FATORES_FORA_DO_HOST && !$usuario->validaHost($remoteAddr)) {
                $msgErroAutenticacao = 'Você não possui privilégio para acessar o sistema deste local ' . $remoteAddr . '!';
                throw new LoginException($msgErroAutenticacao, LoginException::CODE_FORBIDDEN);
            }

            if ($senhaValida && $usuario) {
                if (!$this->getEngine()->isDev() && $usuario->getSecret2FA() && ($usuario->getTipoAuth2FA() == Usuario::TIPO_DOIS_FATORES_SEMPRE || $usuario->getTipoAuth2FA() == Usuario::TIPO_DOIS_FATORES_FORA_DO_HOST)) {
                    // Se o usuário informou uma nova senha não solicita o token novamente, se ele chegou nesta parte significa que já validou o token
                    $check2FA = is_null($novaSenha);

                    // somente exige o 2fa se autenticado fora do range de hosts do usuário
                    if ($check2FA && !$code2FA && $usuario->getTipoAuth2FA() == Usuario::TIPO_DOIS_FATORES_FORA_DO_HOST) {
                        $check2FA = !$usuario->validaHost($remoteAddr);
                    }

                    if ($check2FA && !$code2FA) {
                        Model\Usuario2FAException::throw2FARequired();
                    } else if ($check2FA) {
                        $googleAuth = new \Google\Authenticator\GoogleAuthenticator();
                        if (!$googleAuth->checkCode($usuario->getSecret2FA(), $code2FA)) {
                            Model\Usuario2FAException::throw2FAInvalidCode();
                        }
                    }
                }

                // Se os dados do usuário estão corretos mas a senha está expirada
                if ($usuario->getTipo() == Usuario::TIPO_ADMINISTRATIVO && $usuario->getOpcoesSenha()->getSenhaExpirada()) {
                    if (!is_null($novaSenha)) {
                        if ($novaSenha == $senha) {
                            throw new LoginException('Nova senha não pode ser igual a senha antiga.', LoginException::CODE_PASSWORD_EXPIRED);
                        } else {
                            $usuario->setSenhaCadastro($novaSenha);
                            $usuario->setDataAlteracaoSenha(new \DateTime());
                            $usuario->getOpcoesSenha()->setSenhaExpirada(false);
                            $this->getEntityManager()->persist($usuario);
                        }
                    } else {
                        throw new LoginException('Senha expirada! É necessário gerar uma nova para poder utilizar o sistema.', LoginException::CODE_PASSWORD_EXPIRED);
                    }
                }
            }
        }

        $log = new Model\Log\LogLogin();
        $log->setSucesso(false);
        $log->setIp($remoteAddr);
        $log->setLogin($email);
        $log->setTipo($tipo);

        if ($usuario === null || !$senhaValida) {
            $log->setMensagem($msgErroAutenticacao);
        } else if ($usuario->getSituacao() !== Usuario::SITUACAO_ATIVO) {
            switch ($usuario->getSituacao()) {
                case Usuario::SITUACAO_PENDENTE:
                    $log->setMensagem('Seu cadastro está pendente de aprovação. Para maiores informações entre em contato com nossos atendentes.');
                    break;
                case Usuario::SITUACAO_REJEITADO:
                    $log->setMensagem('Seu cadastro não foi aprovado. Para maiores informações entre em contato com nossos atendentes.');
                    break;
                default:
                    $log->setMensagem('Seu login e senha não conferem. Entre em contato com nossos atendentes.');
                    break;
            }
        } else {
            if ($registerSession) {
                $this->getSession()->init();
                $this->getSession()->set('usuario', $usuario);
            } else {
                $this->getSession()->setRequest('usuario', $usuario);
            }
            $log->setUsuario($usuario);
            $log->setSucesso(true);

            if ($tipo == Usuario::TIPO_ECOMMERCE) {
                $tokenRepository = $this->getEntityManager()->getRepository('Model\UsuarioToken');
                $token = $tokenRepository->findOneBy(array('usuario' => $usuario, 'userAgent' => $userAgent));
                if (!$token) {
                    $token = new Model\UsuarioToken($usuario);
                    $token->setUserAgent($userAgent);
                }
                if ($lembrar) {
                    $token->setDataAtualizacao(new \DateTime());
                    $this->getSession()->setCookie('utoklog', $token->getHash(), strtotime('+30 days'), '/', null, null, true);
                } else {
                    $this->getSession()->removeCookie('utoklog');
                }
                $this->getEntityManager()->persist($token);
                $this->getSession()->setCookie('utokcar', $token->getHash(), strtotime('+180 days'), '/', null, null, true);
                $usuario->setUsuarioToken($token);
            }
        }

        // Inserção em SQL - Performance
        $this->getEntityManager()->getRepository(Model\Log\LogLogin::class)->insereSql($log);
        if (!$log->getSucesso()) {
            throw new LoginException($log->getMensagem());
        }
        $this->getEntityManager()->flush();
        return $usuario;
    }
}