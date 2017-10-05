<?php

require './vendor/autoload.php';

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\GraphQL;
use GraphQL\Schema;

$userType = new ObjectType([
    'name' => 'User',
    'description' => 'Usuário que escreveu a notícia',
    'fields' => [
        'id' => Type::nonNull(Type::id()),
        'name' => Type::nonNull(Type::string()),
        'email' => Type::nonNull(Type::string()),
        'role' => Type::nonNull(Type::string()),
        'created_at' => Type::nonNull(Type::int())
    ]
]);

$mediaType = new ObjectType([
    'name' => 'Media',
    'description' => 'Arquivos de mídia do banco de notícias',
    'fields' => [
        'id' => Type::nonNull(Type::id()),
        'type' => Type::nonNull(Type::string()),
        'filename' => Type::nonNull(Type::string()),
        'size' => Type::nonNull(Type::string()),
        'url' => Type::nonNull(Type::string())
    ]
]);

$commentType = new ObjectType([
    'name' => 'Comment',
    'description' => 'Comentários das notícias',
    'fields' => [
        'id' => Type::nonNull(Type::id()),
        'text' => Type::nonNull(Type::string()),
        'created_at' => Type::nonNull(Type::int()),
        'author' => Type::nonNull(new ObjectType([
            'name' => 'Author',
            'fields' => [
                'name' => Type::string(),
                'email' => Type::string()
            ]
        ]))
    ]
]);

$newsType = new ObjectType([
    'name' => 'News',
    'description' => 'Notícias',
    'fields' => [
        'id' => Type::nonNull(Type::id()),
        'user' => Type::nonNull($userType),
        'media' => $mediaType,
        'title' => Type::nonNull(Type::string()),
        'excerpt' => [
            'type' => Type::nonNull(Type::string()),
            'resolve' => function ($news, $args) {
                return 'Excerpt from: ' . $news->title;
            }
        ],
        'text' => Type::nonNull(Type::string()),
        'created_at' => Type::nonNull(Type::int()),
        'status' => Type::nonNull(Type::string()),
        'comments' => Type::listOf($commentType)
    ]
]);

$registerUserMutation = [
    'type' => $userType,
    'description' => 'Registra um usuário',
    'args' => [
        'name' => Type::nonNull(Type::string()),
        'email' => Type::nonNull(Type::string()),
    ],
    'resolve' => function ($root, $args) {
        return [
            'id' => rand(50, 100),
            'name' => $args['name'],
            'email' => $args['email'],
            'role' => 'USER',
            'created_at' => time()
        ];
    }
];

$mutationType = new ObjectType([
    'name' => 'Mutation',
    'fields' => [
        'registerUser' => $registerUserMutation
    ]
]);

$newsQuery = [
    'type' => Type::listOf($newsType),
    'args' => [
        'id' => Type::id(),
    ],
    'resolve' => function ($root, $args) {
        //$id = $args['id'];
        return json_decode(file_get_contents('gql_full.json'));
    }
];

$queryType = new ObjectType([
    'name' => 'Query',
    'fields' => [
        'news' => $newsQuery
    ],
]);

$schema = new Schema([
    'query' => $queryType,
    'mutation' => $mutationType
]);

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);
$query = $input['query'];

try {
    $result = GraphQL::execute($schema, $query);
} catch (\Exception $e) {
    $result = [
        'error' => [
            'message' => $e->getMessage()
        ]
    ];
}

header('Content-Type: application/json; charset=UTF-8');
echo json_encode($result);