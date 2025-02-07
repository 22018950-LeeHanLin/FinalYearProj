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

        stage('Build and Test Containers') {
            when {
                expression { env.DEPLOY_STATUS == 'Proceed to Build' }
            }
            parallel {
                stage('Build Apache Image') {
                    steps {
                        script {
                            sh "docker build -t ${DOCKER_WEB_IMAGE} -f ${CONTAINER_FILES_PATH}/Dockerfile.web ${CONTAINER_FILES_PATH}"
                            echo "Apache image built successfully."
                        }
                    }
                }
                stage('Build MySQL Image') {
                    steps {
                        script {
                            sh "docker build -t ${DOCKER_DB_IMAGE} -f ${CONTAINER_FILES_PATH}/Dockerfile.db ${CONTAINER_FILES_PATH}"
                            echo "MySQL image built successfully."
                        }
                    }
                }
            }
        }

        stage('CURL Test') {
            steps {
                script {
                    def response = sh(script: "curl -Is http://localhost:8081/index2.php | head -n 1", returnStdout: true).trim()
                    echo "CURL Response: ${response}"
                    if (!response.contains('200 OK')) {
                        error("CURL test failed")
                    }
                }
            }
        }

        stage('Gatekeeper Approval for Deployment') {
            steps {
                script {
                    def deployStatus = input message: 'Proceed to Deploy Production Environment?', ok: 'Proceed', parameters: [
                        choice(name: 'DEPLOY_STATUS', choices: ['Deploy', 'Rollback'], description: 'Deployment Status')
                    ]
                    env.DEPLOY_STATUS = deployStatus
                }
            }
        }

        stage('Deploy Containers') {
            when {
                expression { env.DEPLOY_STATUS == 'Deploy' }
            }
            steps {
                script {
                    echo "Stopping and removing any existing containers to avoid conflicts..."
                    sh """
                        docker ps -a | grep '${WEB_CONTAINER}' && docker stop ${WEB_CONTAINER} && docker rm ${WEB_CONTAINER} | echo 'No existing Apache container found'
                        docker ps -a | grep '${DB_CONTAINER}' && docker stop ${DB_CONTAINER} && docker rm ${DB_CONTAINER} | echo 'No existing MySQL container found'
                    """

                    echo "Deploying containers..."
                    sh "docker-compose -f ${CONTAINER_FILES_PATH}/docker-compose1.yml up -d"
                }
            }
        }

        stage('Post Deployment CURL Test') {
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
                    if (env.CURL_TEST_FAILED == 'true') {
                        def deployStatus = input message: 'CURL test failed. Rollback?', ok: 'Proceed', parameters: [
                            choice(name: 'DEPLOY_STATUS', choices: ['Rollback', 'End'], description: 'Final Decision')
                        ]
                        env.DEPLOY_STATUS = deployStatus
                    }
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
                    echo "Performing website availability check..."
                    sh "curl -Ishttp://localhost:8081/index2.php | head -n 1"
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
