// THE FILE WAS AUTOGENERATED USING PHP-CONVERTER. PLEASE DO NOT EDIT IT!

export type FullName = {
  firstName: string;
  lastName: string;
};

export type Profile = {
  name: FullName | null | string;
  age: number;
};

export type UserCreate = {
  id: string;
  profile: Profile | null;
};
