pipeline {
    agent any

    environment {
        SONAR_HOST = 'http://localhost:9000'
        SONAR_PROJECT_KEY = 'jenkin'
        SONARQUBE_SERVER_NAME = 'sonarserver' // Ensure this matches Jenkins SonarQube installation
        DOCKER_WEB_IMAGE = 'apache-image'
        DOCKER_DB_IMAGE = 'mysql-image'
        WEB_CONTAINER = 'apache-container'
        DB_CONTAINER = 'mysql-container'
        GIT_REPO = 'https://github.com/22018950-LeeHanLin/FinalYearProj.git'
        LOG_FOLDER = 'pipeline-logs'
        CONTAINER_FILES_PATH = '/var/lib/jenkins/workspace/container-files'
    }

    stages {
        stage('Prepare Environment') {
            steps {
                script {
                    sh "mkdir -p ${CONTAINER_FILES_PATH}"
                    if (fileExists("${CONTAINER_FILES_PATH}/xampp-linux-x64-8.2.12-0-installer.run")) {
                        echo "XAMPP installer already exists."
                    } else {
                        sh "wget https://sourceforge.net/projects/xampp/files/latest/download -O ${CONTAINER_FILES_PATH}/xampp-linux-x64-8.2.12-0-installer.run"
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

        stage('Build and Test Containers') {
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

        stage('Run SonarQube Analysis') {
            steps {
                script {
                    def scannerHome = tool name: 'jenkin', type: 'hudson.plugins.sonar.SonarRunnerInstallation'
                    withSonarQubeEnv("${SONARQUBE_SERVER_NAME}") { 
                        sh "${scannerHome}/bin/sonar-scanner -Dsonar.projectKey=${SONAR_PROJECT_KEY} -Dsonar.host.url=${SONAR_HOST}"
                    }
                    echo "SonarQube scan completed."
                }
            }
        }

        stage('Gatekeeper Approval') {
            steps {
                script {
                    def deployStatus = input message: 'Proceed to deploy or rollback?', ok: 'Proceed', parameters: [
                        choice(name: 'DEPLOY_STATUS', choices: ['good', 'bad'], description: 'Deployment Status')
                    ]
                    env.DEPLOY_STATUS = deployStatus
                }
            }
        }

        stage('Deploy Containers') {
            when {
                expression { env.DEPLOY_STATUS == 'good' }
            }
            steps {
                script {
                    echo "Stopping and removing any existing containers to avoid conflicts..."
                    sh """
                    docker ps -a | grep '${WEB_CONTAINER}' && docker stop ${WEB_CONTAINER} && docker rm ${WEB_CONTAINER} || echo 'No existing Apache container found'
                    docker ps -a | grep '${DB_CONTAINER}' && docker stop ${DB_CONTAINER} && docker rm ${DB_CONTAINER} || echo 'No existing MySQL container found'
                    """

                    echo "Deploying containers..."
                    sh """
                    docker network ls | grep -q container-files_container_network || docker network create container-files_container_network
                    docker-compose -f ${CONTAINER_FILES_PATH}/docker-compose.yml up -d
                    """
                }
            }
        }

        stage('Rollback') {
            when {
                expression { env.DEPLOY_STATUS == 'bad' }
            }
            steps {
                script {
                    echo "Rollback initiated."
                    sh "${CONTAINER_FILES_PATH}/rollback.sh"
                    echo "Performing website availability check..."
                    sh "curl -Is http://localhost:8081 | head -n 1"
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
