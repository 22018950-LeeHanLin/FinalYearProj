pipeline {
    agent any

    environment {
        SONAR_HOST = 'http://127.0.0.1:9000'
        SONAR_PROJECT_KEY = 'jenkin'
        SONARQUBE_SERVER_NAME = 'sonarserver'
        DOCKER_WEB_IMAGE = 'apache-old-image'
        DOCKER_DB_IMAGE = 'mysql-old-image'
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
                stage('Build Apache Image for UAT') {
                    steps {
                        script {
                            sh "docker build -t ${DOCKER_WEB_IMAGE}-uat -f ${CONTAINER_FILES_PATH}/Dockerfile-uat-old.web ${CONTAINER_FILES_PATH}"
                            echo "Apache UAT image built successfully."
                        }
                    }
                }
                stage('Build MySQL Image for UAT') {
                    steps {
                        script {
                            sh "docker build -t ${DOCKER_DB_IMAGE}-uat -f ${CONTAINER_FILES_PATH}/Dockerfile-uat-old.db ${CONTAINER_FILES_PATH}"
                            echo "MySQL UAT image built successfully."
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

                    echo "Deploying containers..."
                    sh "docker compose -f ${CONTAINER_FILES_PATH}/docker-compose-uat-old.yml up -d"
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
                    sh "${CONTAINER_FILES_PATH}/rollback.sh"
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
                stage('Build Apache Image for Production') {
                    steps {
                        script {
                            sh "docker build -t ${DOCKER_WEB_IMAGE}-prod -f ${CONTAINER_FILES_PATH}/Dockerfile-prod-old.web ${CONTAINER_FILES_PATH}"
                            echo "Apache Production image built successfully."
                        }
                    }
                }
                stage('Build MySQL Image for Production') {
                    steps {
                        script {
                            sh "docker build -t ${DOCKER_DB_IMAGE}-prod -f ${CONTAINER_FILES_PATH}/Dockerfile-prod-old.db ${CONTAINER_FILES_PATH}"
                            echo "MySQL Production image built successfully."
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

                    echo "Deploying containers..."
                    sh "docker compose -f ${CONTAINER_FILES_PATH}/docker-compose-prod-old.yml up -d"
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
                    sh "${CONTAINER_FILES_PATH}/rollback.sh"
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
}
