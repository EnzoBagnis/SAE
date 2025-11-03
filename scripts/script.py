from manage import jsonAttempts2data, jsonExercises2data
NC1014 = jsonAttempts2data('NewCaledonia_1014.json')


NCExercises = jsonExercises2data('NewCaledonia_exercises.json')

from code2aes import Code2Aes

aes = Code2Aes(NC1014[0],NCExercises)

from aes2vec import learnModel, inferVectors

model = learnModel(NC1014)

results = inferVectors(model, NC1014)
for r in results:
    print(r)