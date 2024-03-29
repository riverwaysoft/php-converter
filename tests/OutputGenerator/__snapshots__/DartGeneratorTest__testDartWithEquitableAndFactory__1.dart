// THE FILE WAS AUTOGENERATED USING PHP-CONVERTER. PLEASE DO NOT EDIT IT!
import 'package:equatable/equatable.dart';

class Category extends Equatable {
  final String id;
  final String title;
  final int rating;
  final List<Recipe> recipes;

  Category({
    required this.id,
    required this.title,
    required this.rating,
    required this.recipes,
  });

  factory Category.fromJson(Map<String, dynamic> json) {
    return Category(
      id: json['id'],
      title: json['title'],
      rating: json['rating'],
      recipes: List<Recipe>.from(json['recipes'].map((e) => Recipe.fromJson(e))),
    );
  }

  @override
  List<dynamic> get props => [id, title, rating, recipes];
}

enum ColorEnum {
  RED,
  GREEN,
  BLUE,
}

enum DayOfTheWeekEnumBackedInt {
  NONE,
  MONDAY,
  TUESDAY,
  WEDNESDAY,
  THURSDAY,
  FRIDAY,
  SATURDAY,
  SUNDAY,
}

enum DayOfTheWeekEnumBackedString {
  MONDAY,
  TUESDAY,
  WEDNESDAY,
  THURSDAY,
  FRIDAY,
  SATURDAY,
  SUNDAY,
}

class Recipe extends Equatable {
  final String id;
  final String? imageUrl;
  final String? url;
  final bool isCooked;
  final num weight;

  Recipe({
    required this.id,
    this.imageUrl,
    this.url,
    required this.isCooked,
    required this.weight,
  });

  factory Recipe.fromJson(Map<String, dynamic> json) {
    return Recipe(
      id: json['id'],
      imageUrl: json['imageUrl'] != null ? json['imageUrl'] : null,
      url: json['url'] != null ? json['url'] : null,
      isCooked: json['isCooked'],
      weight: json['weight'],
    );
  }

  @override
  List<dynamic> get props => [id, imageUrl, url, isCooked, weight];
}

class SomeEmptyClas {

  
}

class User extends Equatable {
  final String id;
  final User? bestFriend;
  final Recipe? favoriteRecipe;
  final Recipe recipeRequired;
  final List<User> friendsRequired;
  final List<User>? friendsOptional;
  final ColorEnum themeColor;
  final List<ColorEnum> colors;
  final DayOfTheWeekEnumBackedInt enumInt;
  final DayOfTheWeekEnumBackedString enumString;

  User({
    required this.id,
    this.bestFriend,
    this.favoriteRecipe,
    required this.recipeRequired,
    required this.friendsRequired,
    this.friendsOptional,
    required this.themeColor,
    required this.colors,
    required this.enumInt,
    required this.enumString,
  });

  factory User.fromJson(Map<String, dynamic> json) {
    return User(
      id: json['id'],
      bestFriend: json['bestFriend'] != null ? User.fromJson(json['bestFriend']) : null,
      favoriteRecipe: json['favoriteRecipe'] != null ? Recipe.fromJson(json['favoriteRecipe']) : null,
      recipeRequired: Recipe.fromJson(json['recipeRequired']),
      friendsRequired: List<User>.from(json['friendsRequired'].map((e) => User.fromJson(e))),
      friendsOptional: json['friendsOptional'] != null ? List<User>.from(json['friendsOptional'].map((e) => User.fromJson(e))) : null,
      themeColor: ColorEnum.values[json['themeColor']],
      colors: List<ColorEnum>.from(json['colors'].map((e) => ColorEnum.values[e])),
      enumInt: DayOfTheWeekEnumBackedInt.values[json['enumInt']],
      enumString: DayOfTheWeekEnumBackedString.values.byName(json['enumString']),
    );
  }

  @override
  List<dynamic> get props => [id, bestFriend, favoriteRecipe, recipeRequired, friendsRequired, friendsOptional, themeColor, colors, enumInt, enumString];
}

class UserCreateInput {
  final String? promotedAt;
  final String name;

  UserCreateInput({
    this.promotedAt,
    required this.name,
  });
}
