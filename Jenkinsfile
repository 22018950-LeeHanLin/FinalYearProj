pipeline {
    agent any

    environment {
        SONAR_HOST = 'http://127.0.0.1:9000'
        SONAR_PROJECT_KEY = 'jenkin'
        SONARQUBE_SERVER_NAME = 'sonarserver'
        DOCKER_WEB_IMAGE = 'apache-image'
        DOCKER_DB_IMAGE = 'mysql-image'
        GIT_REPO = 'https://github.com/22018950-LeeHanLin/FinalYearProj.git'
        CONTAINER_FILES_PATH = '/home/fypuser/fyp/Jingyi/container-files'
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
                    def deployStatus = input message: 'Proceed to UAT Build and Test?', ok: 'Proceed', parameters: [
                        choice(name: 'DEPLOY_STATUS', choices: ['Proceed to UAT', 'Rollback'], description: 'Deployment Status')
                    ]
                    env.DEPLOY_STATUS = deployStatus
                }
            }
        }

        stage('Build and Test in UAT') {
            when {
                expression { env.DEPLOY_STATUS == 'Proceed to UAT' }
            }
            parallel {
                stage('Build Apache Image for UAT') {
                    steps {
                        script {
                            sh "docker build -t ${DOCKER_WEB_IMAGE}-uat -f ${CONTAINER_FILES_PATH}/Dockerfile.web ${CONTAINER_FILES_PATH}"
                            echo "Apache UAT image built successfully."
                        }
                    }
                }
                stage('Build MySQL Image for UAT') {
                    steps {
                        script {
                            sh "docker build -t ${DOCKER_DB_IMAGE}-uat -f ${CONTAINER_FILES_PATH}/Dockerfile.db ${CONTAINER_FILES_PATH}"
                            echo "MySQL UAT image built successfully."
                        }
                    }
                }
            }
        }
        stage('Deploy to UAT') {
            steps {
                script {
                    echo "Stopping and removing any existing containers to avoid conflicts..."
                    sh """
                        docker ps -a | grep '${WEB_CONTAINER}' && docker stop ${WEB_CONTAINER} && docker rm ${WEB_CONTAINER} || echo 'No existing Apache container found'
                        docker ps -a | grep '${DB_CONTAINER}' && docker stop ${DB_CONTAINER} && docker rm ${DB_CONTAINER} || echo 'No existing MySQL container found'
                    """

                    echo "Deploying containers..."
                    sh "docker-compose -f ${CONTAINER_FILES_PATH}/docker-compose1.yml up -d"
                }
            }
        }

        stage('UAT CURL Test') {
            steps {
                script {
                    def response = sh(script: "curl -Is http://localhost:8081/index2.php | head -n 1", returnStdout: true).trim()
                    echo "UAT CURL Response: ${response}"
                    if (!response.contains('200 OK')) {
                        error("UAT CURL test failed")
                    }
                }
            }
        }

        stage('Gatekeeper for Production Deployment') {
            steps {
                script {
                    def deployStatus = input message: 'Proceed to Deploy to Production?', ok: 'Proceed', parameters: [
                        choice(name: 'DEPLOY_STATUS', choices: ['Deploy to Production', 'Rollback'], description: 'Deployment Status')
                    ]
                    env.DEPLOY_STATUS = deployStatus
                }
            }
        }
        stage('Build the Production Env') {
            when {
                expression { env.DEPLOY_STATUS == 'Deploy to Production' }
            }
            parallel {
                stage('Build Apache Image for Production') {
                    steps {
                        script {
                            sh "docker build -t ${DOCKER_WEB_IMAGE}-uat -f ${CONTAINER_FILES_PATH}/Dockerfile.web ${CONTAINER_FILES_PATH}"
                            echo "Apache UAT image built successfully."
                        }
                    }
                }
                stage('Build MySQL Image for Production') {
                    steps {
                        script {
                            sh "docker build -t ${DOCKER_DB_IMAGE}-uat -f ${CONTAINER_FILES_PATH}/Dockerfile.db ${CONTAINER_FILES_PATH}"
                            echo "MySQL UAT image built successfully."
                        }
                    }
                }
            }
        }

        stage('Deploy to Production') {
            steps {
                script {
                    echo "Stopping and removing any existing containers to avoid conflicts..."
                    sh """
                        docker ps -a | grep '${WEB_CONTAINER}' && docker stop ${WEB_CONTAINER} && docker rm ${WEB_CONTAINER} || echo 'No existing Apache container found'
                        docker ps -a | grep '${DB_CONTAINER}' && docker stop ${DB_CONTAINER} && docker rm ${DB_CONTAINER} || echo 'No existing MySQL container found'
                    """

                    echo "Deploying containers..."
                    sh "docker-compose -f ${CONTAINER_FILES_PATH}/docker-compose1.yml up -d"
                }
            }
        }


        stage('Post-Production CURL Test') {
            steps {
                script {
                    def response = sh(script: "curl -Is http://localhost:8081/index2.php | head -n 1", returnStdout: true).trim()
                    echo "Post-Production CURL Response: ${response}"
                    if (!response.contains('200 OK')) {
                        env.CURL_TEST_FAILED = 'true'
                    } else {
                        env.CURL_TEST_FAILED = 'false'
                    }
                }
            }
        }

        stage('Final Gatekeeper') {
            steps {
                script {
                    def deployStatus = input message: 'Do you want to rollback or end the deployment?', ok: 'Proceed', parameters: [
                        choice(name: 'DEPLOY_STATUS', choices: ['Rollback', 'End'], description: 'Final Decision')
                    ]
                    env.DEPLOY_STATUS = deployStatus
                }
            }
        }

        stage('Rollback if Needed') {
            when {
                expression { env.DEPLOY_STATUS == 'Rollback' }
            }
            steps {
                script {
                    echo "Rollback initiated."
                    sh "${CONTAINER_FILES_PATH}/rollback.sh"
                    echo "Verifying rollback with CURL..."
                    sh "curl -Is http://localhost:8081/index2.php | head -n 1"
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
