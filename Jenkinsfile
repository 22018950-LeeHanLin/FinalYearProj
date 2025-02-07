pipeline {
    agent any

    environment {
        SONAR_HOST = 'http://127.0.0.1:9000'
        SONAR_PROJECT_KEY = 'jenkin'
        SONARQUBE_SERVER_NAME = 'sonarserver'
        DOCKER_WEB_IMAGE = 'apache-image'
        DOCKER_DB_IMAGE = 'mysql-image'
        WEB_CONTAINER = 'apache-container'
        DB_CONTAINER = 'mysql-container'
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

        stage('Gatekeeper Approval') {
            steps {
                script {
                    def deployStatus = input message: 'Proceed to Build and Test?', ok: 'Proceed', parameters: [
                        choice(name: 'DEPLOY_STATUS', choices: ['Proceed to Build', 'Rollback'], description: 'Deployment Status')
                    ]
                    env.DEPLOY_STATUS = deployStatus
                }
            }
        }

        stage('UAT Environment: Build and Test Containers') {
            when {
                expression { env.DEPLOY_STATUS == 'Proceed to Build' }
            }
            parallel {
                stage('Build UAT Apache Image') {
                    steps {
                        script {
                            sh "docker build -t ${DOCKER_WEB_IMAGE}:uat -f ${CONTAINER_FILES_PATH}/Dockerfile.web ${CONTAINER_FILES_PATH}"
                            echo "UAT Apache image built successfully."
                        }
                    }
                }
                stage('Build UAT MySQL Image') {
                    steps {
                        script {
                            sh "docker build -t ${DOCKER_DB_IMAGE}:uat -f ${CONTAINER_FILES_PATH}/Dockerfile.db ${CONTAINER_FILES_PATH}"
                            echo "UAT MySQL image built successfully."
                        }
                    }
                }
            }
        }

        stage('Deploy UAT Containers') {
            steps {
                script {
                    echo "Deploying UAT Containers..."
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
                        env.CURL_TEST_FAILED = 'true'
                    } else {
                        env.CURL_TEST_FAILED = 'false'
                    }
                }
            }
        }

        stage('Gatekeeper Approval for Production Deployment') {
            steps {
                script {
                    def deployStatus = input message: 'Proceed to Deploy Production Environment?', ok: 'Proceed', parameters: [
                        choice(name: 'DEPLOY_STATUS', choices: ['Deploy', 'Rollback'], description: 'Deployment Status')
                    ]
                    env.DEPLOY_STATUS = deployStatus
                }
            }
        }

        stage('Build Production Environment') {
            when {
                expression { env.DEPLOY_STATUS == 'Deploy' }
            }
            parallel {
                stage('Build Production Apache Image') {
                    steps {
                        script {
                            sh "docker build -t ${DOCKER_WEB_IMAGE} -f ${CONTAINER_FILES_PATH}/Dockerfile.web ${CONTAINER_FILES_PATH}"
                            echo "Production Apache image built successfully."
                        }
                    }
                }
                stage('Build Production MySQL Image') {
                    steps {
                        script {
                            sh "docker build -t ${DOCKER_DB_IMAGE} -f ${CONTAINER_FILES_PATH}/Dockerfile.db ${CONTAINER_FILES_PATH}"
                            echo "Production MySQL image built successfully."
                        }
                    }
                }
            }
        }

        stage('Deploy Production Containers') {
            when {
                expression { env.DEPLOY_STATUS == 'Deploy' }
            }
            steps {
                script {
                    echo "Deploying Production Containers..."
                    sh "docker-compose -f ${CONTAINER_FILES_PATH}/docker-compose1.yml up -d"
                }
            }
        }

        stage('Post-Deployment CURL Test') {
            steps {
                script {
                    def response = sh(script: "curl -Is http://localhost:8081/index2.php | head -n 1", returnStdout: true).trim()
                    echo "Post Deployment CURL Response: ${response}"
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
                    def deployStatus = input message: 'CURL Test Completed. Rollback or End?', ok: 'Proceed', parameters: [
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
                    echo "Verifying rollback with CURL test..."
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
