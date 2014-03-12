--------------------------------
TypoScript Configuration Example
--------------------------------

mod.web_txxmlimportM1 {

	general {

		noEdit = BOOLEAN
		noImport = BOOLEAN
		noBatchImport = BOOLEAN

		submitForm {
			noUploadField = BOOLEAN
			noSelectField = BOOLEAN
		}

		recordBrowser {
			enable = BOOLEAN
			stepSize = INTEGER
		}

		debug = BOOLEAN
	}

	source {
		# standard file to import
		# file = PATH

		# standard directory to select files from
		directory = PATH

		# NSprefix = STRING
		# reportDocTag = BOOLEAN

		entryNode = STRING
		entryNode.expression = XPATH
	}
	
	destination {

		SIMPLE_TABLE {

			dontValidateFields = FIELDNAME1, FIELDNAME2
			identifiers = FIELDNAME3

			fields {

				FIELDNAME4.value = INTEGER

				FIELDNAME5.data = TSFE:cObj|data|div2|1|list|item|1

			}
		}

		TABLE_WITH_MULTIPLE_RECORDS {

			dontValidateTablename = BOOLEAN
			dontValidateFields = FIELDNAME1, FIELDNAME2

			recordNode.expression = XPATH EXPRESSION

			fields {

				FIELDNAME3.value = INTEGER

				FIELDNAME4.cObject = XPATH
				FIELDNAME4.cObject {
				}
			}
		}

	}
}	