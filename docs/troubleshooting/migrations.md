# Fix unfinished migrations

Consider the scenario, where your artisan migrate:db fails, due to a script remaining in pending state **forever (don't confuse with slow migrations)**. You can see it, when running artisan migrate:status:

```shell
2024_02_02_124646_add_creating_user_id_codebook_table ....... Pending 
```

In that case you need to manually set their batch to "completed". Fortunately it's pretty easy.
Run `php artisan tinker`, paste the following command and hit Enter to execute:

```php
DB::table('migrations')->insert([
    'migration' => '2024_02_02_124646_add_creating_user_id_codebook_table',
    'batch' => 1
]);
```

It should return `true` if successful. Now check your status again using artisan migrate:status to see, if it's now resolved. You need to execute this step for every migration file that is in pending state.