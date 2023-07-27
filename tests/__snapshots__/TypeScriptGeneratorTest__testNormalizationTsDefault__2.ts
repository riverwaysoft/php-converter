// THE FILE WAS AUTOGENERATED USING PHP-CONVERTER. PLEASE DO NOT EDIT IT!

export type CloudNotify = {
  id: string;
  fcmToken: string | null;
};

export type Response<T> = {
  data: T;
};

export type UserCreate = {
  achievements: string[];
  matrix: number[][];
  name: string | null;
  duplicatesInType: string | number | null;
  age: number | string | number;
  isApproved: boolean | null;
  latitude: number;
  longitude: number;
  mixed: any;
};
