<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<title>GraphQL documentation</title>
	</head>
	<body>
		<div id="app"></div>

		<script src="https://cdnjs.cloudflare.com/ajax/libs/react/15.0.2/react.js"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/react/15.0.2/react-dom.js"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/fetch/1.0.0/fetch.min.js"></script>
		<script src="https://github.com/mhallin/graphql-docs/releases/download/v0.2.0/graphql-docs.min.js"></script>
		<script>
		function fetcher(query) {
			return fetch('{{ route('graphql.query.post') }}', {
				method: 'POST',
				headers: {
					Accept: 'application/json',
					'Content-Type': 'application/json',
				},
				body: JSON.stringify({
					query: query,
				}),
			}).then(function(r) {
				return r.json();
			});
		}

        ReactDOM.render(React.createElement(GraphQLDocs.GraphQLDocs, { fetcher: fetcher }), document.querySelector("#app"));
		</script>
	</body>
	</html>
