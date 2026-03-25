pipeline{
	agent { label "Linux" }
	options {
		skipDefaultCheckout(true)
	}
	stages{
		stage("Checkout"){
			steps{
				cleanWs()
				dir("Lang"){
					git url: "https://github.com/NumbatLogic/Lang",
						branch: "main",
						credentialsId: 'c532651f-f9a2-48a8-8a37-8df46a9c5ee2'
				}
				dir("LangShared"){
					git url: "https://github.com/NumbatLogic/LangShared",
						branch: "main",
						credentialsId: 'c532651f-f9a2-48a8-8a37-8df46a9c5ee2'
				}
				dir("ProjectGen"){
					git url: "https://github.com/NumbatLogic/ProjectGen",
						branch: "main",
						credentialsId: 'c532651f-f9a2-48a8-8a37-8df46a9c5ee2'
				}
				dir("CodeCrab"){
					git url: "https://github.com/NumbatLogic/CodeCrab",
						branch: "main",
						credentialsId: 'c532651f-f9a2-48a8-8a37-8df46a9c5ee2'
				}
				dir("nll-PgSql"){
					git url: "https://github.com/NumbatLogic/nll-PgSql",
						branch: "main",
						credentialsId: 'c532651f-f9a2-48a8-8a37-8df46a9c5ee2'
				}
				dir("gsMigrator"){
					git url: "https://github.com/NumbatLogic/gsMigrator",
						branch: "main",
						credentialsId: 'c532651f-f9a2-48a8-8a37-8df46a9c5ee2'
				}
			}
		}

		stage("Build"){
			steps{
				sh "cd gsMigrator && ./CIRebuild.sh"
			}
		}

		stage("Log Parse"){
			steps{
				logParser ([
					projectRulePath: 'Lang/LogParsingRules',
					parsingRulesPath: '',
					showGraphs: true, 
					unstableOnWarning: true, 
					useProjectRule: true
				])
			}
		}
	}
}