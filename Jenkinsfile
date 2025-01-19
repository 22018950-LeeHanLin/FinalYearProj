pipeline {
    agent any

    environment {
        SONAR_HOST = 'http://localhost:9000'
        SONAR_PROJECT_KEY = 'FYPtesting'
        DOCKER_WEB_IMAGE = 'apache-image'
        DOCKER_DB_IMAGE = 'mysql-image'
        WEB_CONTAINER = 'apache-container'
        DB_CONTAINER = 'mysql-container'
        GIT_REPO = 'https://github.com/22018950-LeeHanLin/FinalYearProj.git'
        GIT_CREDENTIALS = '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'
        GIT_USERNAME = 'githubadmin'
        LOG_FOLDER = 'pipeline-logs'
        CONTAINER_FILES_PATH = '/home/fypuser/fyp/Jingyi/container-files' // Full path to container files
    }

    triggers {
        pollSCM('* * * * *') // Polling every minute
    }

    stages {
        stage('Checkout Code') {
            steps {
                script {
                    git branch: 'main',
                        credentialsId: '0ca8f70f-0dae-44d3-b15d-2d8ad577e89c',
                        url: "${GIT_REPO}"
                    echo "Code checked out from the repository."
                }
            }
        }

        stage('Build and Test Containers') {
            parallel {
                stage('Build Apache Image') {
                    steps {
                        sh "docker build -t ${DOCKER_WEB_IMAGE} -f ${CONTAINER_FILES_PATH}/Dockerfile.web ${CONTAINER_FILES_PATH}"
                        echo "Apache image built: ${DOCKER_WEB_IMAGE}"
                    }
                }
                stage('Build MySQL Image') {
                    steps {
                        sh "docker build -t ${DOCKER_DB_IMAGE} -f ${CONTAINER_FILES_PATH}/Dockerfile.db ${CONTAINER_FILES_PATH}"
                        echo "MySQL image built: ${DOCKER_DB_IMAGE}"
                    }
                }
            }
        }

        stage('Run SonarQube Analysis') {
            steps {
                script {
                    withSonarQubeEnv('SonarQube') {
                        def scannerHome = tool name: 'SonarScanner', type: 'hudson.plugins.sonar.SonarRunnerInstallation'
                        sh "${scannerHome}/bin/sonar-scanner -Dsonar.projectKey=${SONAR_PROJECT_KEY} -Dsonar.host.url=${SONAR_HOST}"
                    }
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
                    echo "Deploying production containers..."
                    sh "docker-compose -f ${CONTAINER_FILES_PATH}/docker-compose.yml up -d"
                }
            }
        }

        stage('Rollback') {
            when {
                expression { env.DEPLOY_STATUS == 'bad' }
            }
            steps {
                sh "${CONTAINER_FILES_PATH}/rollback.sh"
                echo "Rollback executed."
            }
        }

        stage('Log Results to GitHub') {
            steps {
                script {
                    sh """
                    mkdir -p ${LOG_FOLDER}
                    echo 'Pipeline execution log' > ${LOG_FOLDER}/log.txt
                    git config --global user.email "you@example.com"
                    git config --global user.name "Your Name"
                    git add ${LOG_FOLDER}
                    git commit -m 'Pipeline logs updated'
                    git push https://${GIT_USERNAME}:${GIT_CREDENTIALS}@${GIT_REPO}
                    """
                    echo "Logs uploaded to GitHub."
                }
            }
        }
    }

    post {
        success {
            echo 'Pipeline executed successfully!'
        }
        failure {
            echo 'Pipeline failed. Check logs in GitHub.'
        }
    }
}
