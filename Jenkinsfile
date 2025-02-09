pipeline {
    agent any

    environment {
        SONAR_HOST = 'http://127.0.0.1:9000'
        SONAR_PROJECT_KEY = 'jenkin'
        SONARQUBE_SERVER_NAME = 'sonarserver'
        DOCKER_WEB_IMAGE_UAT_NEW = 'apache-uat-new-image'
        DOCKER_DB_IMAGE_UAT_NEW = 'mysql-uat-new-image'
        DOCKER_WEB_IMAGE_UAT_OLD = 'apache-uat-old-image'
        DOCKER_DB_IMAGE_UAT_OLD = 'mysql-uat-old-image'
        DOCKER_WEB_IMAGE_PROD_NEW= 'apache-prod-new-image'
        DOCKER_DB_IMAGE_PROD_NEW ='mysql-prod-new-image'
        DOCKER_WEB_IMAGE_PROD_OLD ='apache-prod-old-image'
        DOCKER_DB_IMAGE_PROD_OLD ='mysql-prod-old-image' 
        WEB_CONTAINER = 'apache-container'
        DB_CONTAINER = 'mysql-container'
        GIT_REPO = 'https://github.com/22018950-LeeHanLin/FinalYearProj.git'
        CONTAINER_FILES_PATH = '/home/fypuser/fyp/Hari/webdeploy'
    }

    stages {
        stage('Prepare Environment') {
            steps {
                script {
                    sh "mkdir -p ${CONTAINER_FILES_PATH}"
                    if (fileExists("${CONTAINER_FILES_PATH}/xampp-linux-x64-8.2.12-0-installer.run")) {
                        echo "XAMPP installer already exists."
                    } else {
                        sh "wget --no-check-certificate https://sourceforge.net/projects/xampp/files/latest/download -O ${CONTAINER_FILES_PATH}/xampp-linux-x64-8.2.12-0-installer.run"
                    }
                }
            }
        }

        stage('Checkout Code') {
            steps {
                script {
                    git branch: 'main', url: "${GIT_REPO}"
                    echo "Code checked out from the repository."
                }
            }
        }

        stage('SonarQube Analysis') {
            steps {
                withSonarQubeEnv('sonarserver') {
                    withCredentials([string(credentialsId: 'sonar-token', variable: 'SONAR_TOKEN')]) {
                        sh """
                            /opt/sonar-scanner/bin/sonar-scanner \
                            -Dsonar.projectKey=jenkin \
                            -Dsonar.host.url=${SONAR_HOST} \
                            -Dsonar.login=$SONAR_TOKEN
                        """
                    }
                }
            }
        }

        stage('Gatekeeper for old UAT Deployment') {
            steps {
                script {
                    def UAT_DEPLOY_STATUS = input message: 'Proceed to UAT Build and Test?', ok: 'Proceed', parameters: [
                        choice(name: 'UAT_DEPLOY_STATUS', choices: ['Proceed to UAT', 'Rollback'], description: 'Deployment Status')
                    ]
                    env.UAT_DEPLOY_STATUS = UAT_DEPLOY_STATUS
                }
            }
        }

    
        stage('Clean Up Docker Environment') {
            steps {
                script {
                    echo "Cleaning up all Docker containers, images, networks, and volumes..."
                    sh '''
                        docker stop $(docker ps -aq) || true
                        docker rm $(docker ps -aq) || true
                        docker rmi -f $(docker images -aq) || true
                        docker network prune -f || true
                        docker volume prune -f || true
                    '''
                    echo "Docker environment cleanup completed."
                }
            }
        }
        

        stage('Build and Test in old UAT') {
            when {
                expression { env.UAT_DEPLOY_STATUS == 'Proceed to UAT' }
            }
            parallel {
                stage('Build Apache Image for old UAT') {
                    steps {
                        script {
                             sh "docker build -t ${DOCKER_WEB_IMAGE_UAT_OLD} -f ${CONTAINER_FILES_PATH}/Dockerfile-uat-old.web ${CONTAINER_FILES_PATH}"
                            echo "Apache UAT image built successfully."
                        }
                    }
                }
                stage('Build MySQL Image for old UAT') {
                    steps {
                        script {
                            sh "docker build -t ${DOCKER_DB_IMAGE_UAT_OLD} -f ${CONTAINER_FILES_PATH}/Dockerfile-uat-old.db ${CONTAINER_FILES_PATH}"
                            echo "MySQL UAT image built successfully."
                        }
                    }
                }
            }
        }

        stage('Deploy to old UAT') {
            when {
                expression { env.UAT_DEPLOY_STATUS == 'Proceed to UAT' }
            }
             steps {
                script {
                    echo "Stopping and removing any existing containers to avoid conflicts..."
                    sh """
                        docker ps -a | grep '${WEB_CONTAINER}' && docker stop ${WEB_CONTAINER} && docker rm ${WEB_CONTAINER} || echo 'No existing Apache container found'
                        docker ps -a | grep '${DB_CONTAINER}' && docker stop ${DB_CONTAINER} && docker rm ${DB_CONTAINER} || echo 'No existing MySQL container found'
                    """

                    echo "Deploying containers..."
                    sh "docker compose -f ${CONTAINER_FILES_PATH}/docker-compose-uat-old.yml --project-name uat_old up -d"
                }
            }
        }


        stage('Rollback for old UAT') {
            when {
                expression { env.UAT_DEPLOY_STATUS == 'Rollback' }
            }
            steps {
                script {
                    echo "Rollback initiated for UAT."
                    sh "${CONTAINER_FILES_PATH}/rollback-uat.sh"
                }
            }
        }

        stage('UAT CURL Test') {
            when {
                expression { env.UAT_DEPLOY_STATUS == 'Proceed to UAT' }
            }
            steps {
                script {
                    def ports = [8081, 8082, 8087, 8088]
                    ports.each { port ->
                        def response = sh(script: "curl -Is http://localhost:${port}/ | head -n 1", returnStdout: true).trim()
                        echo "UAT CURL Response for port ${port}: ${response}"
                        if (!response.contains('200 OK')) {
                            error("UAT CURL test failed on port ${port}")
                        }
                    }
                }
            }
        }

        stage('Gatekeeper for old Production Deployment') {
            steps {
                script {
                    def PROD_DEPLOY_STATUS = input message: 'Proceed to Deploy to Production?', ok: 'Proceed', parameters: [
                        choice(name: 'PROD_DEPLOY_STATUS', choices: ['Deploy to Production', 'Rollback'], description: 'Deployment Status')
                    ]
                    env.PROD_DEPLOY_STATUS = PROD_DEPLOY_STATUS
                }
            }
        }

        stage('Build for old Production') {
            when {
                expression { env.PROD_DEPLOY_STATUS == 'Deploy to Production' }
            }
            parallel {
                stage('Build Apache Image for old Production') {
                    steps {
                        script {
                            sh "docker build -t ${DOCKER_WEB_IMAGE_PROD_OLD} -f ${CONTAINER_FILES_PATH}/Dockerfile-prod-old.web ${CONTAINER_FILES_PATH}"
                            echo "Apache Production image built successfully."
                        }
                    }
                }
                stage('Build MySQL Image for old Production') {
                    steps {
                        script {
                           sh "docker build -t ${DOCKER_DB_IMAGE_PROD_OLD} -f ${CONTAINER_FILES_PATH}/Dockerfile-prod-old.db ${CONTAINER_FILES_PATH}"
                        }
                    }
                }
            }
        }

        stage('Deploy to old Production') {
            when {
                expression { env.PROD_DEPLOY_STATUS == 'Deploy to Production' }
            }
             steps {
                script {
                    echo "Stopping and removing any existing containers to avoid conflicts..."
                    sh """
                        docker ps -a | grep '${WEB_CONTAINER}' && docker stop ${WEB_CONTAINER} && docker rm ${WEB_CONTAINER} || echo 'No existing Apache container found'
                        docker ps -a | grep '${DB_CONTAINER}' && docker stop ${DB_CONTAINER} && docker rm ${DB_CONTAINER} || echo 'No existing MySQL container found'
                    """

                    echo "Deploying containers..."
                    sh "docker compose -f ${CONTAINER_FILES_PATH}/docker-compose-prod-old.yml --project-name prod_old up -d"
                }
            }
        }


        stage('Rollback for old Production') {
            when {
                expression { env.PROD_DEPLOY_STATUS == 'Rollback' }
            }
            steps {
                script {
                    echo "Rollback initiated for Production."
                    sh "${CONTAINER_FILES_PATH}/rollback-prod.sh"
                }
            }
        }
      //  stage('PROD CURL Test') {
           // when {
              //  expression { env.PROD_DEPLOY_STATUS == 'Deploy to Production' }
          //  }
           // steps {
                //script {
                   // def response = sh(script: "curl -Is http://localhost:8085/ | head -n 1", returnStdout: true).trim()
                  //  echo "UAT CURL Response: ${response}"
                   // if (!response.contains('200 OK')) {
                     //   error("UAT CURL test failed")
                   // }
               // }
           // }
      //  }
        
stage('Gatekeeper for UAT Deployment') {
            steps {
                script {
                    def UAT_DEPLOY_STATUS = input message: 'Proceed to UAT Build and Test?', ok: 'Proceed', parameters: [
                        choice(name: 'UAT_DEPLOY_STATUS', choices: ['Proceed to UAT', 'Rollback'], description: 'Deployment Status')
                    ]
                    env.UAT_DEPLOY_STATUS = UAT_DEPLOY_STATUS
                }
            }
        }

        stage('Build and Test in UAT') {
            when {
                expression { env.UAT_DEPLOY_STATUS == 'Proceed to UAT' }
            }
            parallel {
                stage('Build Apache New Image for UAT') {
                    steps {
                        script {
                            sh "docker build -t ${DOCKER_WEB_IMAGE_UAT_NEW} -f ${CONTAINER_FILES_PATH}/Dockerfile-uat-new.web ${CONTAINER_FILES_PATH}"
                            echo "Apache NEW UAT image built successfully."
                        }
                    }
                }
                stage('Build MySQL New Image for UAT') {
                    steps {
                        script {
                            sh "docker build -t ${DOCKER_DB_IMAGE_UAT_NEW} -f ${CONTAINER_FILES_PATH}/Dockerfile-uat-new.db ${CONTAINER_FILES_PATH}"
                            echo "MySQL NEW UAT image built successfully."
                        }
                    }
                }
            }
        }

        stage('Deploy to UAT') {
            when {
                expression { env.UAT_DEPLOY_STATUS == 'Proceed to UAT' }
            }
             steps {
                script {
                    echo "Stopping and removing any existing containers to avoid conflicts..."
                    sh """
                        docker ps -a | grep '${WEB_CONTAINER}' && docker stop ${WEB_CONTAINER} && docker rm ${WEB_CONTAINER} || echo 'No existing Apache container found'
                        docker ps -a | grep '${DB_CONTAINER}' && docker stop ${DB_CONTAINER} && docker rm ${DB_CONTAINER} || echo 'No existing MySQL container found'
                    """

                   echo "Deploying NEW containers to UAT..."
                    sh "docker compose -f ${CONTAINER_FILES_PATH}/docker-compose-uat-new.yml --project-name uat_new up -d"
                }
            }
        }


        stage('Rollback for UAT') {
            when {
                expression { env.UAT_DEPLOY_STATUS == 'Rollback' }
            }
            steps {
                script {
                    echo "Rollback initiated for UAT."
                    sh "${CONTAINER_FILES_PATH}/rollback-uat.sh"
                }
            }
        }

       // stage('UAT CURL Test') {
          //  when {
            //    expression { env.UAT_DEPLOY_STATUS == 'Proceed to UAT' }
           // }
          //  steps {
            //    script {
              //      def response = sh(script: "curl -Is http://localhost:8085/ | head -n 1", returnStdout: true).trim()
                //    echo "UAT CURL Response: ${response}"
                //    if (!response.contains('200 OK')) {
                 //       error("UAT CURL test failed")
                 //   }
              //  }
           // }
      //  }

        stage('Gatekeeper for Production Deployment') {
            steps {
                script {
                    def PROD_DEPLOY_STATUS = input message: 'Proceed to Deploy to Production?', ok: 'Proceed', parameters: [
                        choice(name: 'PROD_DEPLOY_STATUS', choices: ['Deploy to Production', 'Rollback'], description: 'Deployment Status')
                    ]
                    env.PROD_DEPLOY_STATUS = PROD_DEPLOY_STATUS
                }
            }
        }

        stage('Build for Production') {
            when {
                expression { env.PROD_DEPLOY_STATUS == 'Deploy to Production' }
            }
            parallel {
                stage('Build Apache New Image for Production') {
                    steps {
                        script {
                            sh "docker build -t ${DOCKER_WEB_IMAGE_PROD_NEW} -f ${CONTAINER_FILES_PATH}/Dockerfile-prod-new.web ${CONTAINER_FILES_PATH}"
                            echo "Apache NEW Production image built successfully."
                        }
                    }
                }
                stage('Build MySQL New Image for Production') {
                    steps {
                        script {
                           sh "docker build -t ${DOCKER_DB_IMAGE_PROD_NEW} -f ${CONTAINER_FILES_PATH}/Dockerfile-prod-new.db ${CONTAINER_FILES_PATH}"
                            echo "MySQL images built successfully."
                            echo "MySQL NEW Production image built successfully."
                        }
                    }
                }
            }
        }

        stage('Deploy to Production') {
            when {
                expression { env.PROD_DEPLOY_STATUS == 'Deploy to Production' }
            }
             steps {
                script {
                    echo "Stopping and removing any existing containers to avoid conflicts..."
                    sh """
                        docker ps -a | grep '${WEB_CONTAINER}' && docker stop ${WEB_CONTAINER} && docker rm ${WEB_CONTAINER} || echo 'No existing Apache container found'
                        docker ps -a | grep '${DB_CONTAINER}' && docker stop ${DB_CONTAINER} && docker rm ${DB_CONTAINER} || echo 'No existing MySQL container found'
                    """

                    echo "Deploying NEW containers to Production..."
                    sh "docker-compose -f ${CONTAINER_FILES_PATH}/docker-compose-prod-new.yml --project-name prod_new up -d"
                }
            }
        }


        stage('Rollback for Production') {
            when {
                expression { env.PROD_DEPLOY_STATUS == 'Rollback' }
            }
            steps {
                script {
                    echo "Rollback initiated for Production."
                    sh "${CONTAINER_FILES_PATH}/rollback-prod.sh"
                }
            }
        }
      //  stage('PROD CURL Test') {
           // when {
              //  expression { env.PROD_DEPLOY_STATUS == 'Deploy to Production' }
          //  }
           // steps {
                //script {
                   // def response = sh(script: "curl -Is http://localhost:8085/ | head -n 1", returnStdout: true).trim()
                  //  echo "UAT CURL Response: ${response}"
                   // if (!response.contains('200 OK')) {
                     //   error("UAT CURL test failed")
                   // }
               // }
           // }
      //  }
        stage('Final Gatekeeper') {
            steps {
                script {
                    def FINAL_DEPLOY_STATUS = input message: 'Do you want to rollback or end the deployment?', ok: 'Proceed', parameters: [
                        choice(name: 'FINAL_DEPLOY_STATUS', choices: ['Rollback', 'End'], description: 'Final Decision')
                    ]
                    env.FINAL_DEPLOY_STATUS = FINAL_DEPLOY_STATUS
                }
            }
        }

        stage('Rollback if Needed') {
            when {
                expression { env.FINAL_DEPLOY_STATUS == 'Rollback' }
            }
            steps {
                script {
                    echo "Rollback initiated."
                    sh "${CONTAINER_FILES_PATH}/rollback.sh"
                }
            }
        }
    }

    post {
        success {
            echo 'Pipeline executed successfully!'
        }
        failure {
            echo 'Pipeline failed. Check logs for details.'
        }
    }

